<?php
namespace Ext\BEAR\Resource\Annotation;

use Ray\Di\Di\Qualifier;

/**
 * @Annotation
 * @Target("METHOD")
 * @Qualifier
 */
final class ResourceDelegate
{
    /**
     * @var string
     */
    public $type;
}
