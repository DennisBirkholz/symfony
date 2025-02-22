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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;

class TestController extends Controller
{
    use ControllerTrait {
        generateUrl as public;
        redirect as public;
        forward as public;
        getUser as public;
        json as public;
        file as public;
        isGranted as public;
        denyAccessUnlessGranted as public;
        redirectToRoute as public;
        addFlash as public;
        isCsrfTokenValid as public;
        renderView as public;
        render as public;
        stream as public;
        createNotFoundException as public;
        createAccessDeniedException as public;
        createForm as public;
        createFormBuilder as public;
        getDoctrine as public;
        addLink as public;
    }
}
