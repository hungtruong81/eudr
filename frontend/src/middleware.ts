// middleware.ts
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

const PUBLIC_PATHS = [
  "/login",
  "/register",
  "/signup",
  "/forgot-password",
  "/reset-password",
  "/verify-email",
  "/",
  "/about",
  "/contact",
  "/privacy",
  "/terms",
  "/tracking",
  "/voucher",
];

const PROTECTED_PATHS = [
  "/user",
  "/user/list",

  "/dashboard",
  "/profile",

  "/land",
  "/land/land-list",
  "/land/map",
  "/land/plants",

  "/farmer",
  "/farmer/farmer-list",
  "/farmer/harvest-plan",
  "/farmer/harvest",

  "/voucher/manage-purchase",
  "/voucher/manage-sale",

  "/support/land",

  "/connection",
  "/connection/statistics-connect",
  "/connection/statistics-share",

  "/route-manage",
  "/route-manage/vehicle",

  "/factory",
  "/factory/receive-material",
  "/factory/production-management",
  "/factory/ticket-product",
  "/factory/fg-receipt-summary",

  "/tank/raw-material",
  "/tank/product",

  "/product-type",

  "/management/company",
  "/management/group-permission",
  "/management/user-management",

  "/sale/customer",
  "/sale/order",
  "/sale/issue",
];

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Lấy access_token từ cookie (HttpOnly cũng đọc được ở server-side)
  const accessToken = request.cookies.get("eudr_2025_access_token")?.value;

  const isLoggedIn = !!accessToken;

  // 1. Đã login + đang cố vào trang login → đẩy về trang chủ
  if (isLoggedIn && pathname === "/login") {
    return NextResponse.redirect(new URL("/connection", request.url));
  }

  // 2. Chưa login + truy cập trang bảo vệ → đẩy về login
  const isProtectedPath = PROTECTED_PATHS.some((path) =>
    pathname.startsWith(path)
  );

  const isPublicPath =
    pathname === "/" ||
    PUBLIC_PATHS.filter((p) => p !== "/").some((path) =>
      pathname.startsWith(path)
    );

  if (!isLoggedIn && isProtectedPath && !isPublicPath) {
    const loginUrl = new URL("/login", request.url);
    loginUrl.searchParams.set("callbackUrl", pathname);
    return NextResponse.redirect(loginUrl);
  }

  // Các trường hợp còn lại → cho qua
  const response = NextResponse.next();

  // Đảm bảo có cookie NEXT_LOCALE
  if (!request.cookies.has("NEXT_LOCALE")) {
    response.cookies.set("NEXT_LOCALE", "vi", { path: "/", maxAge: 31536000 });
  }

  return response;
}

export const config = {
  matcher: [
    /*
     * Match all request paths except:
     * - api routes
     * - _next/static (static files)
     * - _next/image (image optimization)
     * - favicon.ico
     */
    "/((?!api|_next/static|_next/image|favicon.ico).*)",
  ],
};
