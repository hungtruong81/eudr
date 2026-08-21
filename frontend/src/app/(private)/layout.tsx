"use client";

import { usePathname } from "next/navigation";
import dynamic from "next/dynamic";
import { Layout, Breadcrumb as AntdBreadcrumb, theme } from "antd";
import { useTranslations, useLocale } from "next-intl";

import NotificationComponent from "@/components/notification";
import LanguageSwitcher from "@/components/language-switcher";
import { App as AntdApp } from "antd";

const Sidebar = dynamic(() => import("@/components/app-sidebar"), {
  ssr: false,
});

const { Header, Content } = Layout;

export default function PrivateLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const t = useTranslations("Navigation");
  const locale = useLocale();
  const pathName = usePathname();

  const breadcrumbItems = pathName
    .split("/")
    .filter(Boolean)
    .map((segment) => {
      // Chuyển đổi hyphen thành underscore để khớp với key trong JSON, nếu cần
      const key = segment.replace(/-/g, "_");

      // Thử dùng key trực tiếp, nếu không có thì trả về segment gốc
      const label = t.has(key) ? t(key) : segment;

      return {
        title: label,
      };
    });

  const {
    token: { colorBgContainer },
  } = theme.useToken();

  return (
    <Layout hasSider style={{ height: "100vh", overflow: "hidden" }}>
      <Sidebar />
      <Layout style={{ minWidth: 0, overflowY: "auto", overflowX: "hidden" }}>
        <Header
          style={{
            padding: "0 16px",
            background: colorBgContainer,
            borderBottom: "1px solid #f0f0f0",
            position: "sticky",
            top: 0,
            zIndex: 10,
          }}
          className="flex justify-between items-center h-14">
          <div className="flex min-w-0 items-center overflow-hidden">
            <AntdBreadcrumb items={breadcrumbItems} />
          </div>

          <div className="flex shrink-0 items-center gap-2 sm:gap-4">
            <LanguageSwitcher locale={locale} />
            <NotificationComponent />
          </div>
        </Header>

        <Content className="m-2 flex min-w-0 flex-col overflow-x-hidden sm:m-4">
          <div className="flex min-w-0 flex-1 flex-col bg-transparent">
            <AntdApp>{children}</AntdApp>
          </div>
        </Content>
      </Layout>
    </Layout>
  );
}
