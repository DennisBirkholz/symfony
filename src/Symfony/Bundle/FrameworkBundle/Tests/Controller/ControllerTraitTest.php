<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\WebLink\Link;
use Twig\Environment;

abstract class ControllerTraitTest extends TestCase
{
    abstract protected function createController();

    public function testForward()
    {
        $request = Request::create('/');
        $request->setLocale('fr');
        $request->setRequestFormat('xml');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel->expects($this->once())->method('handle')->willReturnCallback(function (Request $request) {
            return new Response($request->getRequestFormat().'--'.$request->getLocale());
        });

        $container = new Container();
        $container->set('request_stack', $requestStack);
        $container->set('http_kernel', $kernel);

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->forward('a_controller');
        $this->assertEquals('xml--fr', $response->getContent());
    }

    public function testGetUser()
    {
        $user = new User('user', 'pass');
        $token = new UsernamePasswordToken($user, 'pass', 'default', ['ROLE_USER']);

        $controller = $this->createController();
        $controller->setContainer($this->getContainerWithTokenStorage($token));

        $this->assertSame($controller->getUser(), $user);
    }

    public function testGetUserAnonymousUserConvertedToNull()
    {
        $token = new AnonymousToken('default', 'anon.');

        $controller = $this->createController();
        $controller->setContainer($this->getContainerWithTokenStorage($token));

        $this->assertNull($controller->getUser());
    }

    public function testGetUserWithEmptyTokenStorage()
    {
        $controller = $this->createController();
        $controller->setContainer($this->getContainerWithTokenStorage(null));

        $this->assertNull($controller->getUser());
    }

    public function testGetUserWithEmptyContainer()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The SecurityBundle is not registered in your application.');
        $controller = $this->createController();
        $controller->setContainer(new Container());

        $controller->getUser();
    }

    private function getContainerWithTokenStorage($token = null): Container
    {
        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    public function testJson()
    {
        $controller = $this->createController();
        $controller->setContainer(new Container());

        $response = $controller->json([]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('[]', $response->getContent());
    }

    public function testJsonWithSerializer()
    {
        $container = new Container();

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with([], 'json', ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS])
            ->willReturn('[]');

        $container->set('serializer', $serializer);

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->json([]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('[]', $response->getContent());
    }

    public function testJsonWithSerializerContextOverride()
    {
        $container = new Container();

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with([], 'json', ['json_encode_options' => 0, 'other' => 'context'])
            ->willReturn('[]');

        $container->set('serializer', $serializer);

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->json([], 200, [], ['json_encode_options' => 0, 'other' => 'context']);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('[]', $response->getContent());
        $response->setEncodingOptions(\JSON_FORCE_OBJECT);
        $this->assertEquals('{}', $response->getContent());
    }

    public function testFile()
    {
        $container = new Container();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $container->set('http_kernel', $kernel);

        $controller = $this->createController();
        $controller->setContainer($container);

        /* @var BinaryFileResponse $response */
        $response = $controller->file(new File(__FILE__));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }
        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('content-disposition'));
        $this->assertStringContainsString(basename(__FILE__), $response->headers->get('content-disposition'));
    }

    public function testFileAsInline()
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $response = $controller->file(new File(__FILE__), null, ResponseHeaderBag::DISPOSITION_INLINE);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }
        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_INLINE, $response->headers->get('content-disposition'));
        $this->assertStringContainsString(basename(__FILE__), $response->headers->get('content-disposition'));
    }

    public function testFileWithOwnFileName()
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $fileName = 'test.php';
        $response = $controller->file(new File(__FILE__), $fileName);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }
        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('content-disposition'));
        $this->assertStringContainsString($fileName, $response->headers->get('content-disposition'));
    }

    public function testFileWithOwnFileNameAsInline()
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $fileName = 'test.php';
        $response = $controller->file(new File(__FILE__), $fileName, ResponseHeaderBag::DISPOSITION_INLINE);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }
        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_INLINE, $response->headers->get('content-disposition'));
        $this->assertStringContainsString($fileName, $response->headers->get('content-disposition'));
    }

    public function testFileFromPath()
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $response = $controller->file(__FILE__);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }
        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('content-disposition'));
        $this->assertStringContainsString(basename(__FILE__), $response->headers->get('content-disposition'));
    }

    public function testFileFromPathWithCustomizedFileName()
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $response = $controller->file(__FILE__, 'test.php');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }
        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('content-disposition'));
        $this->assertStringContainsString('test.php', $response->headers->get('content-disposition'));
    }

    public function testFileWhichDoesNotExist()
    {
        $this->expectException(FileNotFoundException::class);
        $controller = $this->createController();

        $controller->file('some-file.txt', 'test.php');
    }

    public function testIsGranted()
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertTrue($controller->isGranted('foo'));
    }

    public function testdenyAccessUnlessGranted()
    {
        $this->expectException(AccessDeniedException::class);
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);

        $controller = $this->createController();
        $controller->setContainer($container);

        $controller->denyAccessUnlessGranted('foo');
    }

    public function testRenderViewTwig()
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')->willReturn('bar');

        $container = new Container();
        $container->set('twig', $twig);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->renderView('foo'));
    }

    public function testRenderTwig()
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')->willReturn('bar');

        $container = new Container();
        $container->set('twig', $twig);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->render('foo')->getContent());
    }

    public function testStreamTwig()
    {
        $twig = $this->createMock(Environment::class);

        $container = new Container();
        $container->set('twig', $twig);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertInstanceOf(StreamedResponse::class, $controller->stream('foo'));
    }

    public function testRedirectToRoute()
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())->method('generate')->willReturn('/foo');

        $container = new Container();
        $container->set('router', $router);

        $controller = $this->createController();
        $controller->setContainer($container);
        $response = $controller->redirectToRoute('foo');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/foo', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddFlash()
    {
        $flashBag = new FlashBag();
        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('getFlashBag')->willReturn($flashBag);

        $container = new Container();
        $container->set('session', $session);

        $controller = $this->createController();
        $controller->setContainer($container);
        $controller->addFlash('foo', 'bar');

        $this->assertSame(['bar'], $flashBag->get('foo'));
    }

    public function testCreateAccessDeniedException()
    {
        $controller = $this->createController();

        $this->assertInstanceOf(AccessDeniedException::class, $controller->createAccessDeniedException());
    }

    public function testIsCsrfTokenValid()
    {
        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager->expects($this->once())->method('isTokenValid')->willReturn(true);

        $container = new Container();
        $container->set('security.csrf.token_manager', $tokenManager);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertTrue($controller->isCsrfTokenValid('foo', 'bar'));
    }

    public function testGenerateUrl()
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())->method('generate')->willReturn('/foo');

        $container = new Container();
        $container->set('router', $router);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('/foo', $controller->generateUrl('foo'));
    }

    public function testRedirect()
    {
        $controller = $this->createController();
        $response = $controller->redirect('https://dunglas.fr', 301);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://dunglas.fr', $response->getTargetUrl());
        $this->assertSame(301, $response->getStatusCode());
    }

    /**
     * @group legacy
     */
    public function testRenderViewTemplating()
    {
        $templating = $this->createMock(EngineInterface::class);
        $templating->expects($this->once())->method('render')->willReturn('bar');
        $templating->expects($this->once())->method('supports')->willReturn(true);

        $container = new Container();
        $container->set('templating', $templating);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->renderView('foo'));
    }

    /**
     * @group legacy
     */
    public function testRenderTemplating()
    {
        $templating = $this->createMock(EngineInterface::class);
        $templating->expects($this->once())->method('render')->willReturn('bar');
        $templating->expects($this->once())->method('supports')->willReturn(true);

        $container = new Container();
        $container->set('templating', $templating);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->render('foo')->getContent());
    }

    /**
     * @group legacy
     */
    public function testStreamTemplating()
    {
        $templating = $this->createMock(EngineInterface::class);

        $container = new Container();
        $container->set('templating', $templating);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertInstanceOf(StreamedResponse::class, $controller->stream('foo'));
    }

    public function testCreateNotFoundException()
    {
        $controller = $this->createController();

        $this->assertInstanceOf(NotFoundHttpException::class, $controller->createNotFoundException());
    }

    public function testCreateForm()
    {
        $config = $this->createMock(FormConfigInterface::class);
        $config->method('getInheritData')->willReturn(false);
        $config->method('getName')->willReturn('');

        $form = new Form($config);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects($this->once())->method('create')->willReturn($form);

        $container = new Container();
        $container->set('form.factory', $formFactory);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals($form, $controller->createForm('foo'));
    }

    public function testCreateFormBuilder()
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects($this->once())->method('createBuilder')->willReturn($formBuilder);

        $container = new Container();
        $container->set('form.factory', $formFactory);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals($formBuilder, $controller->createFormBuilder('foo'));
    }

    public function testGetDoctrine()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $container = new Container();
        $container->set('doctrine', $doctrine);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals($doctrine, $controller->getDoctrine());
    }

    public function testAddLink()
    {
        $request = new Request();
        $link1 = new Link('mercure', 'https://demo.mercure.rocks');
        $link2 = new Link('self', 'https://example.com/foo');

        $controller = $this->createController();
        $controller->addLink($request, $link1);
        $controller->addLink($request, $link2);

        $links = $request->attributes->get('_links')->getLinks();
        $this->assertContains($link1, $links);
        $this->assertContains($link2, $links);
    }
}
