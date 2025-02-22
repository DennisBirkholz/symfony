<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\Tests\Extension;

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Tests\Extension\Fixtures\StubFilesystemLoader;
use Symfony\Bridge\Twig\Tests\Extension\Fixtures\StubTranslator;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

/**
 * Class providing test cases for the Bootstrap 4 Twig form theme.
 *
 * @author Hidde Wieringa <hidde@hiddewieringa.nl>
 */
class FormExtensionBootstrap4HorizontalLayoutTest extends AbstractBootstrap4HorizontalLayoutTest
{
    use RuntimeLoaderProvider;

    protected $testableFeatures = [
        'choice_attr',
    ];

    private $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $loader = new StubFilesystemLoader([
            __DIR__.'/../../Resources/views/Form',
            __DIR__.'/Fixtures/templates/form',
        ]);

        $environment = new Environment($loader, ['strict_variables' => true]);
        $environment->addExtension(new TranslationExtension(new StubTranslator()));
        $environment->addExtension(new FormExtension());

        $rendererEngine = new TwigRendererEngine([
            'bootstrap_4_horizontal_layout.html.twig',
            'custom_widgets.html.twig',
        ], $environment);
        $this->renderer = new FormRenderer($rendererEngine, $this->createMock(CsrfTokenManagerInterface::class));
        $this->registerTwigRuntimeLoader($environment, $this->renderer);
    }

    protected function renderForm(FormView $view, array $vars = [])
    {
        return $this->renderer->renderBlock($view, 'form', $vars);
    }

    protected function renderLabel(FormView $view, $label = null, array $vars = [])
    {
        if (null !== $label) {
            $vars += ['label' => $label];
        }

        return $this->renderer->searchAndRenderBlock($view, 'label', $vars);
    }

    protected function renderHelp(FormView $view)
    {
        return $this->renderer->searchAndRenderBlock($view, 'help');
    }

    protected function renderErrors(FormView $view)
    {
        return $this->renderer->searchAndRenderBlock($view, 'errors');
    }

    protected function renderWidget(FormView $view, array $vars = [])
    {
        return $this->renderer->searchAndRenderBlock($view, 'widget', $vars);
    }

    protected function renderRow(FormView $view, array $vars = [])
    {
        return $this->renderer->searchAndRenderBlock($view, 'row', $vars);
    }

    protected function renderRest(FormView $view, array $vars = [])
    {
        return $this->renderer->searchAndRenderBlock($view, 'rest', $vars);
    }

    protected function renderStart(FormView $view, array $vars = [])
    {
        return $this->renderer->renderBlock($view, 'form_start', $vars);
    }

    protected function renderEnd(FormView $view, array $vars = [])
    {
        return $this->renderer->renderBlock($view, 'form_end', $vars);
    }

    protected function setTheme(FormView $view, array $themes, $useDefaultThemes = true)
    {
        $this->renderer->setTheme($view, $themes, $useDefaultThemes);
    }
}
