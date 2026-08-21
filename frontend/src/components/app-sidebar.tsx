import React, { useMemo, useState, useEffect } from "react";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Layout, Menu, Spin, Typography } from "antd";
import type { MenuProps } from "antd";
import { useTranslations } from "next-intl";

import { NavUser } from "./nav-user";
import { hasPermission } from "@/lib/permissions";
import { UserConfig } from "@/lib/types";
import { useUser } from "@/providers/user-context";
import { menuConfig } from "@/config/menu-config";
import { MenuFoldOutlined, MenuUnfoldOutlined } from "@ant-design/icons";
import Image from "next/image";

const { Sider } = Layout;
const { Text } = Typography;

export interface MenuItem {
  title: string;
  url: string;
  icon?: any;
  isActive?: boolean;
  isSingle?: boolean;
  requiredPermissions: string[];
  items?: MenuItem[];
}

export const hasAnyPermission = (
  userPermissions: string[],
  requiredPermissions: string[],
): boolean => {
  if (!requiredPermissions || requiredPermissions.length === 0) return true;
  if (!userPermissions || !requiredPermissions?.length) return false;
  return requiredPermissions.some((permission) =>
    hasPermission(userPermissions, permission),
  );
};

export const filterMenuItems = (
  items: MenuItem[],
  userPermissions: string[],
): MenuItem[] => {
  return items
    .map((item) => {
      const hasItemPermission = hasAnyPermission(
        userPermissions,
        item.requiredPermissions,
      );
      if (!hasItemPermission) return null;

      if (item.items) {
        const filteredSubItems = filterMenuItems(item.items, userPermissions);
        if (filteredSubItems.length === 0) return null;
        return { ...item, items: filteredSubItems };
      }
      return item;
    })
    .filter((item): item is MenuItem => item !== null);
};

const defaultUser: UserConfig = {
  userId: "default-user",
  fullName: "Guest User",
  email: "guest@example.com",
  avatar: "/avatars/default.jpg",
  phone: "",
  user_id: 0,
  permissions: [],
  user_role: [],
  register_type: "",
};

const mapToAntdMenu = (
  items: MenuItem[],
  t: (key: string) => string,
): MenuProps["items"] => {
  return items.map((item) => {
    const IconComponent = item.icon;
    const hasChildren = item.items && item.items.length > 0;
    const itemKey = item.url === "#" ? item.title : item.url;

    const labelText = t(item.title);

    return {
      key: itemKey,
      icon: IconComponent ? <IconComponent size={18} /> : null,
      label: hasChildren ? (
        labelText
      ) : (
        <Link href={item.url}>{labelText}</Link>
      ),
      children: hasChildren ? mapToAntdMenu(item.items!, t) : undefined,
    };
  });
};

function AppSidebar() {
  const t = useTranslations("Navigation");
  const { userInfo, isLoading, isFarmer, isInspector, isCompany, isAdmin } =
    useUser();
  const userPermissions = useMemo(
    () => userInfo?.permissions ?? [],
    [userInfo?.permissions],
  );

  const pathname = usePathname();
  const [collapsed, setCollapsed] = useState(false);
  const [openKeys, setOpenKeys] = useState<string[]>([]);

  const filteredMenuItems = useMemo(() => {
    if (!userPermissions.length) return [];
    return filterMenuItems(
      menuConfig(isFarmer, isInspector, isCompany, isAdmin),
      userPermissions,
    );
  }, [userPermissions, isFarmer, isInspector, isCompany, isAdmin]);

  const antdMenuItems = useMemo(
    () => mapToAntdMenu(filteredMenuItems, t),
    [filteredMenuItems, t],
  );

  const { selectedKey, parentKey } = useMemo(() => {
    let selected = "";
    let parent = "";

    const findActive = (items: MenuItem[], parentTitle = "") => {
      for (const item of items) {
        const itemKey = item.url === "#" ? item.title : item.url;

        if (
          item.url !== "#" &&
          (pathname === item.url || pathname.startsWith(`${item.url}/`))
        ) {
          if (item.url.length > selected.length) {
            selected = itemKey;
            parent = parentTitle;
          }
        }

        if (item.items && item.items.length > 0) {
          findActive(item.items, itemKey);
        }
      }
    };

    findActive(filteredMenuItems);
    return { selectedKey: selected, parentKey: parent };
  }, [pathname, filteredMenuItems]);

  useEffect(() => {
    if (!parentKey) return;
    setOpenKeys((prev) =>
      prev.includes(parentKey) ? prev : [...new Set([...prev, parentKey])],
    );
  }, [parentKey]);

  const onOpenChange: MenuProps["onOpenChange"] = (keys) => {
    setOpenKeys(keys);
  };

  const shouldHideForProductionStation =
    pathname === "/factory/production" || pathname.startsWith("/factory/production/");

  if (shouldHideForProductionStation) {
    return null;
  }

  if (isLoading) {
    return (
      <Sider theme="light" width={250}>
        <div className="flex items-center justify-center p-8 text-gray-500">
          <Spin spinning={isLoading} />
        </div>
      </Sider>
    );
  }

  if (filteredMenuItems.length === 0) {
    return (
      <Sider theme="light" width={250} className="border-r ">
        <div className="flex h-full flex-col">
          <div className="flex-1 p-4 text-center">
            <Text type="secondary">{t("no_access")}</Text>
          </div>
          <div className="border-t  p-2">
            <NavUser user={userInfo ?? defaultUser} />
          </div>
        </div>
      </Sider>
    );
  }

  return (
    <Sider
      theme="light"
      width={250}
      collapsible
      collapsed={collapsed}
      onCollapse={setCollapsed}
      collapsedWidth={64}
      trigger={
        <div
          className="flex h-full items-center justify-center"
          aria-label={collapsed ? t("expand") : t("collapse")}
          title={collapsed ? t("expand") : t("collapse")}>
          {collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
        </div>
      }
      breakpoint="lg"
      className="h-screen border-r">
      <div className="flex h-full flex-col">
        <div className="flex h-14 shrink-0 items-center justify-center border-b px-2 font-bold">
          <Image
            src="/logo.jpeg"
            width={collapsed ? 44 : 120}
            height={collapsed ? 44 : 120}
            alt="logo"
          />
        </div>

        <div className="flex-1 overflow-y-auto custom-scrollbar">
          <Menu
            mode="inline"
            selectedKeys={[selectedKey]}
            openKeys={openKeys}
            onOpenChange={onOpenChange}
            style={{ borderRight: 0 }}
            items={antdMenuItems}
          />
        </div>

        <div className="border-t  p-2 shrink-0">
          <NavUser user={userInfo ?? defaultUser} />
        </div>
      </div>
    </Sider>
  );
}

export default AppSidebar;
