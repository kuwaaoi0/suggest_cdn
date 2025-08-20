<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use League\CommonMark\CommonMarkConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class DocsController extends Controller
{
    public function howto(string $slug = null)
    {
        $dir = resource_path('docs/howto');
        if (!is_dir($dir)) {
            abort(404, 'How To docs directory not found: '.$dir);
        }

        $files = collect(File::files($dir))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.md'))
            ->map(function ($f) {
                $parsed = YamlFrontMatter::parse(File::get($f->getRealPath()));
                $title = $parsed->matter('title') ?? pathinfo($f->getFilename(), PATHINFO_FILENAME);
                $slug  = $parsed->matter('slug')  ?? str()->slug($title);
                $order = (int) ($parsed->matter('order') ?? 999);
                return [
                    'title' => $title,
                    'slug'  => $slug,
                    'order' => $order,
                    'body'  => $parsed->body(),
                ];
            })
            ->sortBy('order')
            ->values();

        if ($files->isEmpty()) {
            abort(404, 'No How To documents found.');
        }

        $page = $slug
            ? ($files->firstWhere('slug', $slug) ?? $files->first())
            : $files->first();

        $menu = $files->map(fn($d) => ['title' => $d['title'], 'slug' => $d['slug']])->all();

        $converter = new CommonMarkConverter();
        $html = $converter->convert($page['body'])->getContent();

        return view('docs.howto', [
            'menu'   => $menu,
            'active' => $page['slug'],
            'title'  => $page['title'],
            'html'   => $html,
        ]);
    }
}
