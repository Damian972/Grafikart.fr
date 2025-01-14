<?php

namespace App\Http\Controller;

use App\Infrastructure\Image\SymfonyResponseFactory;
use League\Glide\ServerFactory;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    private string $cachePath;
    private string $resizeKey;
    private string $publicPath;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->cachePath = $parameterBag->get('kernel.project_dir').'/var/images';
        $this->publicPath = $parameterBag->get('kernel.project_dir').'/public';
        $this->resizeKey = $parameterBag->get('image_resize_key');
    }

    /**
     * @Route("/media/resize/{width}/{height}/{path}", requirements={"width"="\d+", "height"="\d+", "path"=".+"}, name="image_resizer")
     */
    public function image(int $width, int $height, string $path, Request $request): Response
    {
        $server = ServerFactory::create([
            'source' => $this->publicPath,
            'cache' => $this->cachePath,
            'driver' => 'imagick',
            'response' => new SymfonyResponseFactory(),
            'defaults' => [
                'q' => 75,
                'fm' => 'jpg',
                'fit' => 'crop',
            ],
        ]);
        [$url] = explode('?', $request->getRequestUri());
        try {
            SignatureFactory::create($this->resizeKey)->validateRequest($url, ['s' => $request->get('s')]);

            return $server->getImageResponse($path, ['w' => $width, 'h' => $height, 'fit' => 'crop']);
        } catch (SignatureException $exception) {
            throw new HttpException(403, 'Signature invalide');
        }
    }

    /**
     * @Route("/media/convert/{path}", requirements={"path"=".+"}, name="image_jpg")
     */
    public function convert(string $path, Request $request): Response
    {
        $server = ServerFactory::create([
            'source' => $this->publicPath,
            'cache' => $this->cachePath,
            'driver' => 'imagick',
            'response' => new SymfonyResponseFactory(),
            'defaults' => [
                'q' => 75,
                'fm' => 'jpg',
                'fit' => 'crop',
            ],
        ]);
        [$url] = explode('?', $request->getRequestUri());
        try {
            SignatureFactory::create($this->resizeKey)->validateRequest($url, ['s' => $request->get('s')]);

            return $server->getImageResponse($path, ['fm' => 'jpg']);
        } catch (SignatureException $exception) {
            throw new HttpException(403, 'Signature invalide');
        }
    }
}
