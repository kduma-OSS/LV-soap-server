<?php


namespace KDuma\SoapServer;

use Illuminate\Support\Str;
use Laminas\Server\Reflection\Prototype;
use Laminas\Server\Reflection\AbstractFunction;
use Laminas\Server\Reflection\ReflectionParameter;
use Laminas\Soap\AutoDiscover\DiscoveryStrategy\DiscoveryStrategyInterface;

class BetterReflectionDiscovery implements DiscoveryStrategyInterface
{
    /**
     * Returns description from phpdoc block
     *
     * @param  AbstractFunction $function
     * @return string
     */
    public function getFunctionDocumentation(AbstractFunction $function)
    {
        return $function->getDescription();
    }

    /**
     * Return parameter type
     *
     * @param  ReflectionParameter $param
     * @return string
     */
    public function getFunctionParameterType(ReflectionParameter $param)
    {
        /** @var \ReflectionParameter $reflection */
        $reflection = $this->getProtectedValue($param, ReflectionParameter::class);

        $type = $reflection->getType();
        if(is_null($type))
            return $param->getType();

        if($type->isBuiltin())
            return $type->getName();

        return Str::start($type->getName(), '\\');
    }

    /**
     * Return function return type
     *
     * @param  AbstractFunction $function
     * @param  Prototype        $prototype
     * @return string
     */
    public function getFunctionReturnType(AbstractFunction $function, Prototype $prototype)
    {
        /** @var \ReflectionFunctionAbstract $reflection */
        $reflection = $this->getProtectedValue($function, AbstractFunction::class);

        $type = $reflection->getReturnType();
        if(is_null($type))
            return $prototype->getReturnType();

        if($type->isBuiltin())
            return $type->getName();

        return Str::start($type->getName(), '\\');
    }

    /**
     * Return true if function is one way (return nothing)
     *
     * @param  AbstractFunction $function
     * @param  Prototype        $prototype
     * @return bool
     */
    public function isFunctionOneWay(AbstractFunction $function, Prototype $prototype)
    {
        return $this->getFunctionReturnType($function, $prototype) == 'void';
    }

    /**
     * @param object $param
     *
     * @param string $class
     *
     * @return object
     */
    protected function getProtectedValue($param, string $class)
    {
        $reflectionProperty = new \ReflectionProperty($class, 'reflection');
        $reflectionProperty->setAccessible(true);
        $reflection = $reflectionProperty->getValue($param);

        return $reflection;
    }
}
