<?php

namespace App\Http\Controllers\ShortLinks;

use App\Http\Controllers\Controller;
use App\Models\ShortLink;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShortLinkQrCodeController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, ShortLink $link): Response
    {
        $this->authorize('view', $link);

        $shortUrl = url('/'.$link->slug);
        $format = strtolower((string) $request->query('format', 'png'));

        if (! in_array($format, ['png', 'svg'], true)) {
            abort(422, 'Format must be png or svg.');
        }

        $qrCode = new QrCode(
            data: $shortUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $inline = $request->boolean('inline');
        $disposition = $inline ? 'inline' : 'attachment';

        if ($format === 'svg') {
            $writer = new SvgWriter;
            $result = $writer->write($qrCode);

            return response($result->getString(), 200, [
                'Content-Type' => $result->getMimeType(),
                'Content-Disposition' => $disposition.'; filename="'.$link->slug.'-qr.svg"',
            ]);
        }

        $writer = new PngWriter;
        $result = $writer->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => $disposition.'; filename="'.$link->slug.'-qr.png"',
        ]);
    }
}
