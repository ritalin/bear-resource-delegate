<?php
namespace Ext\BEAR\Resource;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Aop\Exception\InvalidAnnotationException;
use Ray\Aop\Exception\InvalidMatcherException;

use Doctrine\Common\Annotations\Reader;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\NamedParameterInterface;

class ResourceDelegateIntercepter implements MethodInterceptor
{
    /**
     * @var Reader
     */
    private $reader;
    /**
     * @var NamedParameterInterface
     */
    private $paramHandler;
    
    public function __construct(Reader $reader, NamedParameterInterface $paramHandler)
    {
        $this->reader = $reader;
        $this->paramHandler = $paramHandler;
    }
    
    public function invoke(MethodInvocation $invocation)
    {
        $resource = $invocation->getThis();
        $result = $invocation->proceed();

        $annotation = $this->reader->getMethodAnnotation($invocation->getMethod(), Annotation\ResourceDelegate::class);
        
        if (isset($annotation)) {
            $class = $this->getDelegateClassName($annotation, $resource);
            if (! class_exists($class)) {
                throw new InvalidAnnotationException('Resource Delegate class is not found.');
            }
            
            $method = isset($resource->uri->query['_override']) ? $resource->uri->query['_override'] : $resource->uri->method;
            if (stripos($method, $resource->uri->method) !== 0) {
                throw new InvalidMatcherException('Overriden method must match to original method');
            }
            
            $call = $this->resolveDelegateMethod($method);
            if (! method_exists($class, $call)) {
                throw new InvalidMatcherException('Resource Delegate method is not found');
            }
            
            $delegate = new $class($resource);
            $params = $this->paramHandler->getParameters([$delegate, $call], $resource->uri->query);
            
            return call_user_func_array([$delegate, $call], $params);
        }
        else {
            $result;
        }
    }
    
    public function getDelegateClassName(Annotation\ResourceDelegate $annotation, $resource)
    {
        $class = $annotation->type;
        if (! isset($class)) {
            $class = get_class($resource) . 'Delegate';
        }
        
        return $class;
    }
    
    public function resolveDelegateMethod($method)
    {
        if (preg_match_all('/(?|([a-z0-9]+[A-Z]?)|([A-Z]+))/', strrev($method), $matches, PREG_SET_ORDER) === 0) {
            return 'on' . ucfirst($method);
        } 
        else {
            $result = '';
            foreach (array_reverse($matches) as $m) {
                $result .= ucfirst(strtolower(strrev($m[1])));
            }
            
            return 'on' . $result;
        }
    }
}
