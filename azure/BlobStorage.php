<?php
namespace app\extend\azure;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ContainerACL;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

class BlobStorage
{
    protected $client;

    /**
     * Container 命名规则
     * 1. 以小写字母或数字开头，只能包含字母、数字和 dash(-)。
     * 2. 不能有连续的 dash(-)，dash(-)不能是第一个字符，也不能是最后一个字符。
     * 3. 所有字符小写，总长度为 3-63 字符。
     */
    protected $_containerNameFmt='con-%s-%s';
    protected $_container;
    private $_accountName;
    private $_emulator;
    private $_domain;
    private $_forceContainer;

    /**
     * 取出$config配置生成连接字符串，并获得Blob服务类
     * @param BlobConfig $config
     */
    public function __construct(BlobConfig $config)
    {
        $this->_accountName=$config->getAccountName();
        $this->_emulator=$config->getEmulator();
        $this->_domain=$config->getDomain();
        $this->_forceContainer=$config->getForceUseContainer();
        if($this->_forceContainer){
            $this->_container=$this->_forceContainer;
        }
        $connectString=$this->makeConnectString($config->getAccountName(),$config->getAccountKey());
        $this->client=BlobRestProxy::createBlobService($connectString);
    }

    /**
     * 创建容器
     * @param string|null $containerName
     * @return mixed
     */
    public function create($containerName=null)
    {
        if($this->_forceContainer){
            $this->_container=$this->_forceContainer;
            return $this->_forceContainer;
        }else{
            if($containerName===null){
                $containerName=sprintf($this->_containerNameFmt,substr(date('Ymd'),2),$this->generateRandomString());
            }
            $this->client->createContainer($containerName);
            $this->_container=$containerName;
            $this->setAcl();
            return $this->getContainer();
        }
    }

    /**
     * 设置容器访问权限
     */
    protected function setAcl()
    {
        $acl=ContainerACL::create(PublicAccessType::BLOBS_ONLY);
        $this->client->setContainerAcl($this->_container,$acl);
    }

    /**
     * 上传文件
     * @param string $blobName blob名称
     * @param string $fileToUpload 需要上传的文件的绝对路径
     * @return string path
     */
    public function upload($blobName,$fileToUpload)
    {
        $content = fopen($fileToUpload, "r");
        $this->setAcl();
        $this->client->createBlockBlob($this->_container,$blobName, $content);
        return $this->makeResourcePath($this->_container,$blobName);
    }

    /**
     * 获取blob内容
     * @param string $blobName blob名称
     * @param string|null $containerName 容器名称
     * @return string blob内容
     */
    public function getBlob($blobName,$containerName=null)
    {
        $res=$this->client->getBlob($containerName?$containerName:$this->_container,$blobName)->getContentStream();
        return stream_get_contents($res);
    }

    /**
     * 删除blob
     * @param string $blobName blob名称
     * @param string|null $containerName 容器名称
     */
    public function deleteBlob($blobName,$containerName=null)
    {
        $this->client->deleteBlob($containerName?$containerName:$this->_container,$blobName);
    }

    /**
     * 删除容器
     * @param string|null $containerName 容器名称
     */
    public function deleteContainer($containerName=null)
    {
        $this->client->deleteContainer($containerName?$containerName:$this->_container);
    }

    /**
     * 设置默认容器名称
     * @param string $containerName 容器名称
     */
    public function setContainer($containerName)
    {
        if($this->_forceContainer){
            $this->_container=$this->_forceContainer;
        }else{
            if($containerName!==null){
                $this->_container=$containerName;
            }
        }
    }

    /**
     * 获得容器名称
     * @return string
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * 获取容器下所有blob名称
     * @param string|null $containerName 容器名称
     * @return array
     */
    public function getBlobNames($containerName=null)
    {
        if($containerName===null){
            $containerName=$this->_container;
        }
        $blobNames=[];
        $listBlobsResult=$this->client->listBlobs($containerName);
        foreach($listBlobsResult->getBlobs() as $blob)
        {
            $blobNames[]=$blob->getName();
        }
        return $blobNames;
    }

    /**
     * 获取所有容器名称
     * @return array
     */
    public function getContainerNames()
    {
        $containerNames=[];
        $listContainerResult=$this->client->listContainers();
        foreach($listContainerResult->getContainers() as $container)
        {
            if($this->_forceContainer){
                if($container->getName()==$this->_forceContainer){
                    $containerNames[]=$container->getName();
                }
            }else{
                $containerNames[]=$container->getName();
            }
        }
        return $containerNames;
    }

    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    protected function generateRandomString($length = 6)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * 生成连接字符串
     * @param string $accountName
     * @param string $accountKey
     * @return string 连接字符串
     */
    protected function makeConnectString($accountName='',$accountKey='')
    {
        if($this->_emulator){
            return 'DefaultEndpointsProtocol=http;AccountName=devstoreaccount1;
AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;
BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;
TableEndpoint=http://127.0.0.1:10002/devstoreaccount1;
QueueEndpoint=http://127.0.0.1:10001/devstoreaccount1;';
        }else{
            if($this->_domain){
                return "DefaultEndpointsProtocol=http;AccountName={$accountName};AccountKey={$accountKey};BlobEndpoint=http://{$this->_domain}/;";
            }else{
                return "DefaultEndpointsProtocol=http;AccountName={$accountName};AccountKey={$accountKey}";
            }
        }
    }

    /**
     * 生成资源路径
     * @param string $containerName 容器名称
     * @param string $blobName blob名称
     * @return string 资源路径
     */
    public function makeResourcePath($containerName,$blobName)
    {
        if($this->_emulator){
            return "http://127.0.0.1:10000/".$this->_accountName."/{$containerName}/{$blobName}";
        }else{
            if($this->_domain){
                return "http://{$this->_domain}/{$containerName}/{$blobName}";
            }else{
                return "http://".$this->_accountName.".blob.core.windows.net/{$containerName}/{$blobName}";
            }
        }
    }
}