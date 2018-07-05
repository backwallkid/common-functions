<?php
namespace app\extend\azure;

class BlobConfig
{
    //默认值为测试帐号
    private $_accountName='devstoreaccount1';
    private $_accountKey='Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==';
    private $_domain='';
    private $_emulator=true;
    private $_forceUseContainer=false;

    /**
     * BlobConfig constructor.
     * @param array $config [
                                'accountName'=>'',
                                'accountKey'=>'',
                                'emulator'=>'',
                                'domain'=>'',
                                'container'=>'',
                            ];
     */
    public function __construct($config=[])
    {
        $this->setAccountName(isset($config['accountName'])?$config['accountName']:$this->getAccountName());
        $this->setAccountKey(isset($config['accountKey'])?$config['accountKey']:$this->getAccountKey());
        $this->setEmulator(isset($config['emulator'])?$config['emulator']:$this->getEmulator());
        $this->setDomain(isset($config['domain'])?$config['domain']:$this->getDomain());
        $this->setForceUseContainer(isset($config['container'])?$config['container']:$this->getForceUseContainer());
    }

    public function getAccountName()
    {
        return $this->_accountName;
    }

    public function setAccountName($accountName)
    {
        $this->_accountName=$accountName;
    }

    public function getAccountKey()
    {
        return $this->_accountKey;
    }

    public function setAccountKey($accountKey)
    {
        $this->_accountKey=$accountKey;
    }

    public function getEmulator()
    {
        return $this->_emulator;
    }

    public function setEmulator($isEmulator)
    {
        $this->_emulator=$isEmulator;
    }

    public function getDomain()
    {
        return $this->_domain;
    }

    public function setDomain($domain)
    {
        $this->_domain=$domain;
    }

    public function getForceUseContainer()
    {
        return $this->_forceUseContainer;
    }

    public function setForceUseContainer($containerName)
    {
        $this->_forceUseContainer=$containerName;
    }
}