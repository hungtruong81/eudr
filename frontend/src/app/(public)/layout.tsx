"use client";

import React from "react";
import { Layout, Button, Typography, theme, Space, Image } from "antd";
import Link from "next/link";
import { UserOutlined } from "@ant-design/icons";
import { useTranslations } from "next-intl";

const { Header, Content } = Layout;
const { Title } = Typography;

export default function PublicLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const t = useTranslations("Auth");
  const {
    token: { colorBgContainer },
  } = theme.useToken();

  return (
    <Layout style={{ minHeight: "100vh" }}>
      <Header
        style={{
          background: colorBgContainer,
          borderBottom: "1px solid #f0f0f0",
          height: "64px",
          padding: "0 24px",
          display: "flex",
          justifyContent: "space-between",
          alignItems: "center",
          position: "sticky",
          top: 0,
          zIndex: 1000,
        }}>
        <Link
          href="/"
          style={{ display: "flex", alignItems: "center", gap: "12px" }}>
          <Image
            src="/logo.jpeg"
            alt="Logo"
            width={32}
            height={32}
            style={{ borderRadius: "4px" }}
          />
          <Title
            level={4}
            style={{ margin: 0, fontWeight: 700, color: "#29a352" }}>
            EUDR 2025
          </Title>
        </Link>

        <Space>
          <Link href="/login">
            <Button icon={<UserOutlined />}>{t("login")}</Button>
          </Link>
        </Space>
      </Header>

      <Content style={{ padding: "0" }}>
        <div style={{ maxWidth: "1200px", margin: "0 auto", padding: "24px" }}>
          {children}
        </div>
      </Content>

      <footer
        style={{ textAlign: "center", padding: "24px", color: "#8c8c8c" }}>
        © {new Date().getFullYear()} EUDR 2025. {t("copyright")}
      </footer>
    </Layout>
  );
}
