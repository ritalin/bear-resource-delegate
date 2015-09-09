<?php
namespace Ext\BEAR\Resource;

use Ray\Aop\ReflectiveMethodInvocation;
use Ray\Aop\Arguments;
use Doctrine\Common\Annotations\AnnotationReader;
use BEAR\Resource\NamedParameterInterface;
use BEAR\Resource\Uri;

class ResourceStub
{
    public $uri;
    
    public function __construct()
    {
    }
    /**
     * @Annotation\ResourceDelegate
     */
    public function onGet()
    {
    }
}

class ResourceStubDelegate
{
    public function onGetList($value)
    {
        return $value * 100;
    }
}

class NamedParameterStub implements NamedParameterInterface
{
    public function getParameters(array $callable, array $query)
    {
        return [isset($query['value']) ? $query['value'] : 999];
    }
}

class IntercepterHelperMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function when_getting_default_delegate_class()
    {
        $intercepter = new ResourceDelegateIntercepter(new AnnotationReader(), new NamedParameterStub());
        $annotation = new Annotation\ResourceDelegate();
        
        $this->assertEquals(ResourceStub::class . 'Delegate', $intercepter->getDelegateClassName($annotation, new ResourceStub()));
    }
    
    /**
     * @test
     */
    public function when_getting_delegate_class()
    {
        $intercepter = new ResourceDelegateIntercepter(new AnnotationReader(), new NamedParameterStub());
        $annotation = new Annotation\ResourceDelegate();
        $annotation->type = 'Ext\BEAR\Resource\CustomResourceDelegate';
        
        $this->assertEquals('Ext\BEAR\Resource\CustomResourceDelegate', $intercepter->getDelegateClassName($annotation, new ResourceStub()));
    }
    
    /**
     * @test
     */
    public function test_get_delegate_method()
    {
        $intercepter = new ResourceDelegateIntercepter(new AnnotationReader(), new NamedParameterStub());
        $this->assertEquals('onPost', $intercepter->resolveDelegateMethod('post'));
        $this->assertEquals('onPost', $intercepter->resolveDelegateMethod('POST'));
        $this->assertEquals('onGetList', $intercepter->resolveDelegateMethod('getList'));
        $this->assertEquals('onGetList', $intercepter->resolveDelegateMethod('GETList'));
    }
    
    /**
     * @test
     */
    public function test_invoke_delegate_method()
    {
        $intercepter = new ResourceDelegateIntercepter(new AnnotationReader(), new NamedParameterStub());
        
        $resource = new ResourceStub();
        $resource->uri = new Uri('app://self/foo', ['_override' => 'getList', 'value' => 9]);
        $resource->uri->method = 'get';
        
        $invokation = new ReflectiveMethodInvocation($resource, new \ReflectionMethod(ResourceStub::class, 'onGet'), new Arguments());
        
        $this->assertEquals(900, $intercepter->invoke($invokation));
    }
}
