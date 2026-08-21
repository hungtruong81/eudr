"use client";

import { ConfigProvider, theme } from "antd";
import { PropsWithChildren } from "react";
import "dayjs/locale/vi";
import viVN from "antd/locale/vi_VN";

dayjs.locale("vi");
const { defaultAlgorithm, darkAlgorithm } = theme;
import dayjs from "dayjs";
import "dayjs/locale/vi";

dayjs.locale("vi");

function getCssVar(name: string) {
  if (typeof window === "undefined") return "";
  return getComputedStyle(document.documentElement)
    .getPropertyValue(name)
    .trim();
}

function hslVar(name: string) {
  const value = getCssVar(name);
  return `hsl(${value})`;
}

export function AntdProvider({ children }: PropsWithChildren) {
  const isDark =
    typeof document !== "undefined" &&
    document.documentElement.classList.contains("dark");

  return (
    <ConfigProvider
      // componentSize="small"
      theme={{
        algorithm: isDark ? darkAlgorithm : defaultAlgorithm,
        token: {
          // colorPrimary: hslVar("--primary"),
          // colorSuccess: hslVar("--primary"),
          // colorWarning: hslVar("--accent"),
          // colorError: hslVar("--destructive"),
          // colorBgBase: hslVar("--background"),
          // colorBgContainer: hslVar("--card"),
          // colorTextBase: hslVar("--foreground"),
          // colorBorder: hslVar("--border"),
          // borderRadius: 8,
          // fontFamily: "Inter, Roboto, sans-serif",
        },
        components: {
          Form: { itemMarginBottom: 12, verticalLabelPadding: "0 0 2px" },
        },
      }}
      locale={viVN}>
      {children}
    </ConfigProvider>
  );
}
