<?php


namespace Alpixel\Bundle\LoggingBundle\Processor;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * @author Benjamin HUBERT <benjamin@alpixel.fr>
 */
class ClientInformationProcessor
{

    private $requestStack;
    private $cachedClientIp = null;

    use ContainerAwareTrait;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function __invoke(array $record)
    {
        // Ensure we have a request (maybe we're in a console command)
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return $record;
        }

        // If we do, get the client's IP, and cache it for later.
        $this->cachedClientIp = $request->getClientIp();

        switch ($request->getMethod()) {
            case Request::METHOD_POST:
                $postData = $request->request->all();
                break;
            default:
                $postData = $request->getQueryString();
                break;
        }

        $token = $this->container->get('security.token_storage')->getToken();
        $userName = "Anonyme";
        if ($token !== null) {
            if ($token->getUser() !== null) {
                $user = $token->getUser();

                $userName = (string)$user;
                try {
                    if ($user->getId() !== null) {
                        $userName = $userName.sprintf(" (ID: %s)", $user->getId());
                    }
                } catch (\Exception $e) {

                }
            }
        }

        $record['extra']['URL'] = "[".$request->getMethod()."] ".$request->getSchemeAndHttpHost(
            ).$request->getRequestUri();
        $record['extra']['Données'] = json_encode($postData);
        $record['extra']['IP Utilisateur'] = $this->cachedClientIp;
        $record['extra']['User Agent'] = $request->headers->get('User-Agent');
        $record['extra']['Nom utilisateur'] = $userName;

        return $record;
    }

}