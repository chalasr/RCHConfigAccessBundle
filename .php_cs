<?php

$finder = \PhpCsFixer\Finder::create()
    ->in(array(__DIR__))
;

$header = <<<EOF
This file is part of the RCHConfigAccessBundle package.

(c) Robin Chalas <robin.chalas@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@Symfony' => true,
        'short_array_syntax' => true,
        'unalign_double_arrow' => false,
        'unalign_equals' => false,
        'align_double_arrow' => true,
        'blank_line_after_opening_tag' => true,
        'single_blank_line_before_namespace' => false,
        'ordered_imports' => true,
        'header_comment' => array('header' => $header)
    ))
    ->setUsingCache(false)
    ->finder($finder)
;
