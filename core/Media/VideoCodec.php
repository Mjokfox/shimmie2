<?php

declare(strict_types=1);

namespace Shimmie2;

enum VideoCodec: string
{
    case VP8 = "vp8";
    case VP9 = "vp9";
    case THEORA = "theora";
    case MPEG4 = "mpeg4";
    case H264 = "h264";
    case H265 = "h265";
}
