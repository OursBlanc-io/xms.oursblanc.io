<?php

namespace OursBlanc\Xms\Rendering;

class ViewResolver
{
    public function __construct(protected ThemeManager $theme) {}

    public function blockView(string $blockClass): string
    {
        return $blockClass::view();
    }

    public function layout(?string $template): string
    {
        if ($template) {
            foreach ($this->layoutCandidates($template) as $candidate) {
                if (view()->exists($candidate)) {
                    return $candidate;
                }
            }
        }

        $themeDefault = ThemeManager::VIEW_NAMESPACE.'::layouts.default';

        if ($this->theme->hasViewNamespace() && view()->exists($themeDefault)) {
            return $themeDefault;
        }

        return 'xms::layouts.default';
    }

    /**
     * @return array<int, string>
     */
    protected function layoutCandidates(string $template): array
    {
        $candidates = [];

        if ($this->theme->hasViewNamespace()) {
            $candidates[] = ThemeManager::VIEW_NAMESPACE."::layouts.{$template}";
        }

        $candidates[] = "layouts.{$template}";

        return $candidates;
    }
}
