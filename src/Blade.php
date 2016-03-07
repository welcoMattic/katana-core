<?php

namespace Katana;

use Illuminate\View\Compilers\BladeCompiler;

class Blade
{
    private $bladeCompiler;

    /**
     * Blade constructor.
     *
     * @param BladeCompiler $bladeCompiler
     */
    public function __construct(BladeCompiler $bladeCompiler)
    {
        $this->bladeCompiler = $bladeCompiler;

        $this->registerMarkdownDirective();
    }

    /**
     * Get the blade compiler after extension
     *
     * @return BladeCompiler
     */
    public function getCompiler()
    {
        return $this->bladeCompiler;
    }

    /**
     * Register the markdown blade directives
     *
     * @return void
     */
    private function registerMarkdownDirective()
    {
        $this->bladeCompiler->directive('markdown', function () {
            return "<?php echo \\Katana\\Markdown::parse(<<<'EOT'";
        });

        $this->bladeCompiler->directive('endmarkdown', function () {
            return "\nEOT\n); ?>";
        });
    }
}