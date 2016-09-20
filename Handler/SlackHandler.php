<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alpixel\Bundle\LoggingBundle\Handler;

use Alpixel\Bundle\LoggingBundle\Formatter\SlackFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SocketHandler;
use Monolog\Logger;

/**
 * Sends notifications through Slack API
 *
 * @author Greg Kedzierski <greg@gregkedzierski.com>
 * @author Benjamin HUBERT <benjamin@alpixel.fr>
 * @see    https://api.slack.com/
 */
class SlackHandler extends SocketHandler
{
    /**
     * Slack API token
     * @var string
     */
    private $token;

    /**
     * Slack channel (encoded ID or name)
     * @var string
     */
    private $channel;

    /**
     * Name of a bot
     * @var string
     */
    private $username;

    /**
     * Emoji icon name
     * @var string
     */
    private $iconEmoji;

    /**
     * @var LineFormatter
     */
    private $lineFormatter;

    /**
     * @param  string $token Slack API token
     * @param  string $channel Slack channel (encoded ID or name)
     * @param  string $username Name of a bot
     * @param  string|null $iconEmoji The emoji name to use (or null)
     * @param  int $level The minimum logging level at which this handler will be triggered
     * @param  bool $bubble Whether the messages that are handled can bubble up the stack or not
     * @throws MissingExtensionException If no OpenSSL PHP extension configured
     */
    public function __construct(
        $token,
        $channel,
        $username = 'Monolog',
        $iconEmoji = null,
        $level = Logger::CRITICAL,
        $bubble = true
    ) {
        if (!extension_loaded('openssl')) {
            throw new MissingExtensionException('The OpenSSL PHP extension is required to use the SlackHandler');
        }

        parent::__construct('ssl://slack.com:443', $level, $bubble);

        $this->token = $token;
        $this->channel = $channel;
        $this->username = $username;
        $this->iconEmoji = trim($iconEmoji, ':');
        $this->lineFormatter = new SlackFormatter("%message%");
    }

    /**
     * {@inheritdoc}
     *
     * @param  array $record
     * @return string
     */
    protected function generateDataStream($record)
    {
        $content = $this->buildContent($record);

        return $this->buildHeader($content).$content;
    }

    /**
     * Builds the body of API call
     *
     * @param  array $record
     * @return string
     */
    private function buildContent($record)
    {
        $dataArray = $this->prepareContentData($record);

        return http_build_query($dataArray);
    }

    /**
     * Prepares content data
     *
     * @param  array $record
     * @return array
     */
    protected function prepareContentData($record)
    {
        $message = $this->lineFormatter->format($record);

        $dataArray = [
            'text'        => '',
            'token'       => $this->token,
            'channel'     => $this->channel,
            'username'    => $this->username,
            "icon_emoji"  => ":{$this->iconEmoji}:",
            'attachments' => [],
        ];

        $attachment = [
            'title'     => 'Détail de l\'erreur',
            'fallback'  => 'Erreur PHP : '.$message,
            'text'      => "```".$message."```",
            'color'     => $this->getAttachmentColor($record['level']),
            'fields'    => [],
            "mrkdwn_in" => ["text"],
            "footer"    => $record['extra']['URL'],
            "ts"        => time(),
        ];

        foreach ($record['extra'] as $var => $val) {
            $attachment['fields'][] = [
                'title' => $var,
                'value' => $val,
            ];
        }

        $dataArray["attachments"] = json_encode([$attachment]);

        return $dataArray;
    }

    /**
     * Returned a Slack message attachment color associated with
     * provided level.
     *
     * @param  int $level
     * @return string
     */
    protected function getAttachmentColor($level)
    {
        switch (true) {
            case $level >= Logger::ERROR:
                return 'danger';
            case $level >= Logger::WARNING:
                return 'warning';
            case $level >= Logger::INFO:
                return 'good';
            default:
                return '#e3e4e6';
        }
    }

    /**
     * Builds the header of the API Call
     *
     * @param  string $content
     * @return string
     */
    private function buildHeader($content)
    {
        $header = "POST /api/chat.postMessage HTTP/1.1\r\n";
        $header .= "Host: slack.com\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: ".strlen($content)."\r\n";
        $header .= "\r\n";

        return $header;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $record
     */
    protected function write(array $record)
    {
        parent::write($record);
        $res = $this->getResource();
        if (is_resource($res)) {
            @fread($res, 2048);
        }
        $this->closeSocket();
    }

    /**
     * Stringifies an array of key/value pairs to be used in attachment fields
     *
     * @param  array $fields
     * @return string
     */
    protected function stringify($fields)
    {
        $string = '';
        foreach ($fields as $var => $val) {
            $string .= $var.': '.$this->lineFormatter->stringify($val)." | ";
        }

        $string = rtrim($string, " |");

        return $string;
    }
}
