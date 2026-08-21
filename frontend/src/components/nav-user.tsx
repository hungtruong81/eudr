"use client";

import { LogOut, User } from "lucide-react";
import { Avatar, Dropdown } from "antd";
import type { MenuProps } from "antd";
import Link from "next/link";
import { useRouter } from "nextjs-toploader/app";

import { UserConfig } from "@/lib/types";
import { useUser } from "@/providers/user-context";
import { useTranslations } from "next-intl";

export function NavUser({ user }: { user: UserConfig }) {
  const t = useTranslations("Navigation");
  const tc = useTranslations("Common");
  const router = useRouter();
  const { doLogout } = useUser();

  const items: MenuProps["items"] = [
    {
      key: "user-info",
      label: (
        <div className="flex items-center gap-2 py-1">
          <Avatar src={user.avatar} className="rounded-md shrink-0">
            {user.fullName?.charAt(0) || "CN"}
          </Avatar>
          <div className="flex flex-col text-sm">
            <span className="font-semibold truncate">{user.fullName}</span>
            <span className="text-xs text-gray-500 truncate">{user.email}</span>
          </div>
        </div>
      ),
      disabled: true,
      className: "!cursor-default",
    },
    { type: "divider" },
    {
      key: "account",
      icon: <User size={16} />,
      label: <Link href="/account">{t("account")}</Link>,
    },
    { type: "divider" },
    {
      key: "logout",
      icon: <LogOut size={16} />,
      label: tc("logout"),
      onClick: () => {
        router.push("/login");
        doLogout();
      },
      danger: true,
    },
  ];

  return (
    <Dropdown menu={{ items }} trigger={["click"]} placement="topRight">
      <div className="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors w-full">
        <Avatar src={user.avatar} shape="square" className="shrink-0">
          {user.fullName?.charAt(0) || "CN"}
        </Avatar>
        <div className="flex flex-col flex-1 overflow-hidden">
          <span className="text-sm font-semibold truncate text-gray-800">
            {user.fullName}
          </span>
          <span className="text-xs text-gray-500 truncate">{user.email}</span>
        </div>
      </div>
    </Dropdown>
  );
}
