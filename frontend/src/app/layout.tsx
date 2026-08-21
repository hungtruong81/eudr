import ServiceWorker from "@/components/service-worker";
import { cn, constructMetadata } from "@/lib/utils";
import { KeysProvider } from "@/providers/keys-provider";
import { ThemeProvider } from "@/providers/theme-provider";
import { UserProvider } from "@/providers/user-context";
import NextTopLoader from "nextjs-toploader";
import { Toaster } from "sonner";
import { Roboto } from "next/font/google";
import QueryProvider from "@/providers/query-provider";
import type { Metadata, Viewport } from "next";
import "./app.css";
import "./globals.css";
import { Suspense } from "react";
import { GoogleMapsProvider } from "@/providers/google-maps-provider";
import { PermissionProvider } from "@/contexts/permission-context";
import { NextIntlClientProvider } from "next-intl";
import { getMessages } from "next-intl/server";
import { cookies } from "next/headers";
import { defaultLocale, LOCALE_COOKIE } from "@/types/i18n";
import { AntdProvider } from "@/providers/antd-provider";
import { App as AntdApp } from "antd";

export const metadata: Metadata = constructMetadata({});

const roboto = Roboto({
  subsets: ["latin", "vietnamese"],
  weight: ["400", "500", "700"],
  variable: "--font-roboto",
});

export const viewport: Viewport = {
  colorScheme: "light",
  themeColor: [
    { media: "(prefers-color-scheme: light)", color: "white" },
    { media: "(prefers-color-scheme: dark)", color: "black" },
  ],
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const cookieStore = await cookies();
  const locale = cookieStore.get(LOCALE_COOKIE)?.value || defaultLocale;
  const messages = await getMessages();

  return (
    <html lang={locale} suppressHydrationWarning>
      <head>
        <link rel="preconnect" href="/" />
        <meta name="apple-mobile-web-app-title" content="EUDR" />
      </head>
      <body
        className={cn(
          "min-h-screen bg-background antialiased w-full mx-auto scroll-smooth",
          roboto.className,
        )}>
        <ServiceWorker />
        <KeysProvider>
          <GoogleMapsProvider>
            <NextTopLoader
              color="#29a352"
              initialPosition={0.08}
              crawlSpeed={200}
              height={3}
              crawl={true}
              easing="ease"
              speed={200}
              shadow="0 0 10px #2299DD,0 0 5px #2299DD"
              zIndex={1600}
              showAtBottom={false}
            />
            <QueryProvider>
              <UserProvider>
                <PermissionProvider>
                  <ThemeProvider
                    attribute="class"
                    defaultTheme="light"
                    enableSystem={false}>
                    <AntdProvider>
                      <Suspense>
                        <NextIntlClientProvider
                          locale={locale}
                          messages={messages}>
                          <AntdApp>{children}</AntdApp>
                        </NextIntlClientProvider>
                      </Suspense>
                    </AntdProvider>
                  </ThemeProvider>
                </PermissionProvider>
              </UserProvider>
            </QueryProvider>
          </GoogleMapsProvider>
        </KeysProvider>
        <Toaster richColors position="top-right" />
      </body>
    </html>
  );
}
