<?php


namespace KDuma\SoapServer;

use SoapFault;
use Laminas\Soap\Wsdl;
use Laminas\Soap\Server;
use Laminas\Soap\AutoDiscover;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Contracts\Container\Container;
use Laminas\Soap\Server\DocumentLiteralWrapper;
use Illuminate\Routing\Controller as BaseController;
use Laminas\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex;
use Laminas\Soap\Wsdl\ComplexTypeStrategy\ComplexTypeStrategyInterface;

abstract class AbstractSoapServerController extends BaseController
{
    /**
     * @return string Server host class name
     */
    abstract protected function getService(): string;

    /**
     * @return string URL endpoint of server
     */
    abstract protected function getEndpoint(): string;

    /**
     * @return string URL endpoint of WSDL
     */
    abstract protected function getWsdlUri(): string;

    /**
     * @return string Service name (Defaults to server host class basename)
     */
    protected function getName(): string
    {
        return class_basename($this->getService());
    }

    /**
     * @return string[] Fault exception handlers to register
     */
    protected function getFaultExceptionsNames(): array
    {
        return [\Exception::class];
    }

    /**
     * @return string[] Complex types to register
     */
    protected function getTypes(): array
    {
        return [];
    }

    /**
     * @return string[] Class map
     */
    protected function getClassmap(): array
    {
        return [];
    }

    /**
     * @return string[] Additional headers to sent with server responses
     */
    protected function getHeaders(): array
    {
        return config('soap-server.headers.soap');
    }

    /**
     * @return string[] Additional headers to sent with WSDL responses
     */
    protected function getWsdlHeaders(): array
    {
        return config('soap-server.headers.wsdl');
    }

    /**
     * @return ComplexTypeStrategyInterface Complex type strategy
     */
    protected function getStrategy(): ComplexTypeStrategyInterface
    {
        return new ArrayOfTypeComplex;
    }

    /**
     * @return string[] SOAP server options
     */
    protected function getOptions(): array
    {
        return [];
    }

    /**
     * @return bool Check if WSDL extension cache
     */
    protected function getWsdlCacheEnabled(): bool
    {
        return config('soap-server.wsdl_cache_enabled');
    }

    /**
     * @return bool Check if wsdl formatting is enabled
     */
    protected function getFormatWsdlOutput(): bool
    {
        return config('soap-server.wsdl_formatting_enabled');
    }

    public function wsdlProvider(ResponseFactory $responseFactory)
    {
        $this->disableSoapCacheWhenNeeded();

        // Create wsdl object and register type(s).
        $wsdl = new Wsdl('wsdl', $this->getEndpoint());

        $strategy = tap($this->getStrategy(), function (ComplexTypeStrategyInterface $strategy) use ($wsdl) {
            // Set type(s) on strategy object.
            $strategy->setContext($wsdl);

            foreach($this->getTypes() as $key => $class) {
                $strategy->addComplexType($class);
            }
        });

        collect($this->getTypes())->each(fn($class, $key) => $wsdl->addType($class, $key));

        // Auto-discover and output xml.
        $autodiscover = new AutoDiscover($strategy, $this->getEndpoint(), null, array_flip($this->getClassmap()));

        $autodiscover->setBindingStyle(['style' => 'document']);
        $autodiscover->setOperationBodyStyle(['use' => 'literal']);

        $autodiscover->setClass($this->getService());
        $autodiscover->setServiceName($this->getName());
        $autodiscover->setDiscoveryStrategy(new BetterReflectionDiscovery());

        $dom = $autodiscover->generate()->toDomDocument();

        if($this->getFormatWsdlOutput()){
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->normalizeDocument();
        }

        return $responseFactory->make($dom->saveXML(), 200, $this->getWsdlHeaders());
    }

    public function soapServer(Container $container, ResponseFactory $responseFactory)
    {
        $this->disableSoapCacheWhenNeeded();

        try {
            $service = $container->make($this->getService());
            $server = new Server($this->getWsdlUri());
            $server->setClass(new DocumentLiteralWrapper($service));
            $server->registerFaultException($this->getFaultExceptionsNames());
            $server->setClassmap($this->getClassmap());
            $server->setOptions($this->getOptions());

            // Intercept response, then decide what to do with it.
            $server->setReturnResponse(true);
            $response = $server->handle();

            // Deal with a thrown exception that was converted into a SoapFault.
            // SoapFault thrown directly in a service class bypasses this code.
            if ($response instanceof SoapFault) {
                return $responseFactory->make(self::serverFault($response), 500, $this->getHeaders());
            } else {
                return $responseFactory->make($response, 200, $this->getHeaders());
            }
        } catch (\Exception $e) {
            return $responseFactory->make(self::serverFault($e), 500, $this->getHeaders());
        }
    }

    /**
     * Return error response and log stack trace.
     *
     * @param \Exception $exception
     *
     * @return string
     */
    protected static function serverFault(\Exception $exception)
    {
        report($exception);

        $faultcode = 'SOAP-ENV:Server';
        $faultstring = $exception->getMessage();

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
    <SOAP-ENV:Body>
        <SOAP-ENV:Fault>
            <faultcode>{$faultcode}</faultcode>
            <faultstring>{$faultstring}</faultstring>
        </SOAP-ENV:Fault>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;
    }

    protected function disableSoapCacheWhenNeeded(): void
    {
        if (!$this->getWsdlCacheEnabled()) {
            ini_set('soap.wsdl_cache_enable', 0);
            ini_set('soap.wsdl_cache_ttl', 0);
        }
    }
}
