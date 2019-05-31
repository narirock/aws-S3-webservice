<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Constraint\Exception;

class UploadController
{
    private $file_ext =  ['png', 'JPG', 'jpg', 'JPG', 'jpeg', 'JPEG'];

    private $file_size = 1048576 * 2;

    public function put(Request $request)
    {
        if (is_file($request->image)) {
            $arquivo = $request->image;
            if (in_array($arquivo->getClientOriginalExtension(), $this->file_ext)) {
                if ($arquivo->getClientSize() <= $this->file_size) {

                    try {
                        if($request->name != ""){
                            $nome  = $request->name;
                        }else{
                            $nome = round(microtime(true) * 1000) ;
                        }
                        $destino = $request->prefixo . $nome . '.' . $arquivo->getClientOriginalExtension();

                        $s3 = $this->getS3Instance($request->key, $request->secret);
                        $retorno = $s3->putObject(
                            array(
                                'Bucket' => $request->bucket,
                                'Key'    => $destino,
                                'SourceFile' => $arquivo,
                            )
                        );

                        $response = array(
                            'success' => true,
                            'message' => 'arquivo salvo com sucesso',
                            'link'  => $retorno['ObjectURL']
                        );
                    } catch (Exception $e) {
                        $response = array(
                            'success' => false,
                            'message' => 'Não foi possivel salvar o arquivo - ' . $e->getMessage()
                        );
                    }
                } else {
                    $response = array(
                        'success' => false,
                        'message' => 'O arquivo não deve ultrapassar ' . ($this->file_size / 1000) . ' bites - ' . $arquivo->getClientSize()
                    );
                }
            } else {
                $response = array(
                    'success' => false,
                    'message' => 'São aceitos apenas arquivos dos tipos ' . implode(',', $this->file_ext)
                );
            }
        } else {
            $response = array(
                'success' => false,
                'message' => 'um arquivo deve ser enviado'
            );
        }
        return $response;
    }

    public static function getS3Instance($key = null, $secret = null)
    {
        return new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'sa-east-1',
            'credentials' => [
                'key' => $key,
                'secret' => $secret
            ]
        ]);
    }
}
