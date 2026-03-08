<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface {
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response {
        $content = "Access to this page is denied";
        return new Response($content, 403);

//        return $this->render('security/accessdenied.html.twig',
//            array(
//                'title' => 'Security Violation')
//            );
    }
}