<?php
declare(strict_types=1);

namespace App\Application\Utility;

class VN2000Converter
{
    private static float $a = 6378137.0; // Bán kính trục lớn WGS84
    private static float $f = 1 / 298.257223563; // Độ dẹt WGS84

    /**
     * Tự động xác định zone UTM từ tọa độ easting (X).
     */
    private static function detectZone(float $easting): int
    {
        // Giả định VN nằm trong zone 48 hoặc 49, nên dùng mốc giữa 500000
        // Nếu easting lớn hơn 500000 thì có thể là zone 49
        return ($easting > 500000) ? 49 : 48;
    }

    /**
     * Chuyển đổi tọa độ VN2000 (UTM) sang LatLng (WGS84).
     * Nếu không truyền zone, tự động xác định từ easting.
     *
     * @param float $easting  Tọa độ X (VN2000)
     * @param float $northing Tọa độ Y (VN2000)
     * @param int|null $zone  Zone UTM, mặc định tự phát hiện nếu không truyền
     * @return array ['lat' => ..., 'lng' => ...]
     */
    public static function convertUTMToLatLng(float $easting, float $northing, ?int $zone = null): array
    {
        $k0 = 0.9996;
        $e = sqrt(self::$f * (2 - self::$f));

        if ($zone === null) {
            $zone = self::detectZone($easting);
        }

        $x = $easting - 500000.0; // loại bỏ false easting
        $y = $northing;

        $longOrigin = ($zone - 1) * 6 - 180 + 3; // kinh tuyến trục

        $M = $y / $k0;
        $mu = $M / (self::$a * (1 - pow($e, 2) / 4 - 3 * pow($e, 4) / 64 - 5 * pow($e, 6) / 256));
        $e1 = (1 - sqrt(1 - $e * $e)) / (1 + sqrt(1 - $e * $e));

        $J1 = (3 * $e1 / 2 - 27 * pow($e1, 3) / 32);
        $J2 = (21 * pow($e1, 2) / 16 - 55 * pow($e1, 4) / 32);
        $J3 = (151 * pow($e1, 3) / 96);
        $J4 = (1097 * pow($e1, 4) / 512);

        $fp = $mu + $J1 * sin(2 * $mu)
            + $J2 * sin(4 * $mu)
            + $J3 * sin(6 * $mu)
            + $J4 * sin(8 * $mu);

        $e2 = $e * $e / (1 - $e * $e);
        $C1 = $e2 * pow(cos($fp), 2);
        $T1 = pow(tan($fp), 2);
        $R1 = self::$a * (1 - $e * $e) / pow(1 - $e * $e * pow(sin($fp), 2), 1.5);
        $N1 = self::$a / sqrt(1 - $e * $e * pow(sin($fp), 2));

        $D = $x / ($N1 * $k0);

        $Q1 = $N1 * tan($fp) / $R1;
        $Q2 = ($D * $D / 2);
        $Q3 = (5 + 3 * $T1 + 10 * $C1 - 4 * $C1 * $C1 - 9 * $e2) * pow($D, 4) / 24;
        $Q4 = (61 + 90 * $T1 + 298 * $C1 + 45 * $T1 * $T1 - 252 * $e2 - 3 * $C1 * $C1) * pow($D, 6) / 720;

        $lat = $fp - $Q1 * ($Q2 - $Q3 + $Q4);
        $lat = rad2deg($lat);

        $Q5 = $D;
        $Q6 = (1 + 2 * $T1 + $C1) * pow($D, 3) / 6;
        $Q7 = (5 - 2 * $C1 + 28 * $T1 - 3 * pow($C1, 2) + 8 * $e2 + 24 * pow($T1, 2)) * pow($D, 5) / 120;

        $lon = $longOrigin + rad2deg(($Q5 - $Q6 + $Q7) / cos($fp));

        return ['lat' => $lat, 'lng' => $lon];
    }

    public static function convertVN2000ToWGS84($x, $y, $zone)
    {
        // Danh sách các tỉnh với kinh tuyến trục (central meridian)
        $province_zones = [
            "An Giang" => 104.75,
            "Bà Rịa Vũng Tàu" => 107.75,
            "Bắc Cạn" => 106.5,
            "Bắc Giang" => 107,
            "Bạc Liêu" => 105,
            "Bắc Ninh" => 105.5,
            "Bến Tre" => 105.75,
            "Bình Định" => 108.25,
            "Bình Dương" => 105.75,
            "Bình Phước" => 106.25,
            "Bình Thuận" => 108.5,
            "Cà Mau" => 104.5,
            "TP. Cần Thơ" => 105,
            "Cao Bằng" => 105.75,
            "TP. Đà Nẵng" => 107.75,
            "Đắc Nông" => 108.5,
            "Đắk Lắk" => 108.5,
            "Điện Biên" => 103,
            "Đồng Nai" => 107.75,
            "Đồng Tháp" => 105,
            "Gia Lai" => 108.5,
            "Hà Giang" => 105.5,
            "Hà Nam" => 105,
            "TP. Hà Nội" => 105,
            "Hà Tĩnh" => 105.5,
            "Hải Dương" => 105.5,
            "TP. Hải Phòng" => 105.75,
            "Hậu Giang" => 105,
            "TP. Hồ Chí Minh" => 105.75,
            "Hoà Bình" => 106,
            "Hưng Yên" => 105.5,
            "Khánh Hoà" => 108.25,
            "Kiên Giang" => 104.5,
            "Kon Tum" => 107.5,
            "Lai Châu" => 103,
            "Lâm Đồng" => 107.75,
            "Lạng Sơn" => 107.25,
            "Lào Cai" => 104.75,
            "Long An" => 105.75,
            "Nam Định" => 105.5,
            "Nghệ An" => 104.75,
            "Ninh Bình" => 105,
            "Ninh Thuận" => 108.25,
            "Phú Thọ" => 104.75,
            "Phú Yên" => 108.5,
            "Quảng Bình" => 106,
            "Quảng Nam" => 107.75,
            "Quảng Ngãi" => 108,
            "Quảng Ninh" => 107.75,
            "Quảng Trị" => 106.25,
            "Sóc Trăng" => 105.5,
            "Sơn La" => 104,
            "Tây Ninh" => 105.5,
            "Thái Bình" => 105.5,
            "Thái Nguyên" => 106.5,
            "Thanh Hoá" => 105,
            "Thừa Thiên Huế" => 107.0,
            "Tiền Giang" => 105.75,
            "Trà Vinh" => 105.5,
            "Tuyên Quang" => 106,
            "Vĩnh Long" => 105.5,
            "Vĩnh Phúc" => 105,
            "Yên Bái" => 104.75,
        ];

        // Nếu không có tỉnh khớp, mặc định lấy 105 (Hà Nội)
        // $zone = $province_zones[$province_name] ?? 105;

        // Các hằng số
        $a = 6378137.0; // Bán kính trục lớn của ellipsoid WGS84
        $f = 1 / 298.257223563; // Độ dẹt
        $k0 = 0.9999; // Scale factor (VN-2000 sử dụng 0.9999)

        $e2 = 2 * $f - $f * $f;
        $FE = 500000;
        $delta_long = deg2rad($zone); // Kinh tuyến trục

        // Dịch tọa độ
        $x -= $FE;

        // Tính toán nghịch UTM (VN2000 gần giống UTM)
        $M = $y / $k0;
        $mu = $M / ($a * (1 - $e2 / 4 - 3 * pow($e2,2) / 64 - 5 * pow($e2,3) / 256));

        $e1 = (1 - sqrt(1 - $e2)) / (1 + sqrt(1 - $e2));
        $J1 = (3*$e1/2 - 27*pow($e1,3)/32);
        $J2 = (21*pow($e1,2)/16 - 55*pow($e1,4)/32);
        $J3 = (151*pow($e1,3)/96);
        $J4 = (1097*pow($e1,4)/512);

        $fp = $mu + $J1*sin(2*$mu) + $J2*sin(4*$mu) + $J3*sin(6*$mu) + $J4*sin(8*$mu);

        $C1 = $e2 * pow(cos($fp),2) / (1 - $e2);
        $T1 = pow(tan($fp),2);
        $R1 = $a * (1 - $e2) / pow(1 - $e2 * pow(sin($fp),2), 1.5);
        $N1 = $a / sqrt(1 - $e2 * pow(sin($fp),2));
        $D = $x / ($N1 * $k0);

        // Vĩ độ
        $lat = $fp - ($N1 * tan($fp) / $R1) *
            ($D * $D / 2 - (5 + 3*$T1 + 10*$C1 - 4*$C1*$C1 - 9*($e2/(1-$e2))) * pow($D,4) / 24
            + (61 + 90*$T1 + 298*$C1 + 45*$T1*$T1 - 252*($e2/(1-$e2)) - 3*$C1*$C1) * pow($D,6) / 720);

        // Kinh độ
        $lon = $delta_long + ($D - (1 + 2*$T1 + $C1) * pow($D,3) / 6
            + (5 - 2*$C1 + 28*$T1 - 3*$C1*$C1 + 8*($e2/(1-$e2)) + 24*$T1*$T1) * pow($D,5) / 120) / cos($fp);

        return [
            'lat' => rad2deg($lat),
            'lng' => rad2deg($lon),
        ];
    }
}
