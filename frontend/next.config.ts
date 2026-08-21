import type { NextConfig } from "next";
import createNextIntlPlugin from "next-intl/plugin";

const withNextIntl = createNextIntlPlugin();

const isWeb = process.env.NEXT_PUBLIC_IS_WEB === "true";

const nextConfig: NextConfig = {
  // ...(isWeb ? { output: "export" } : {}),
  /* experimental: {
    turbo: {
    },
  }, */
  devIndicators: false,
  trailingSlash: true,
  reactStrictMode: true,
  images: {
    unoptimized: true,
    localPatterns: [
      {
        pathname: "/assets/images/**",
        search: "",
      },
    ],
  },
};

export default withNextIntl(nextConfig);
