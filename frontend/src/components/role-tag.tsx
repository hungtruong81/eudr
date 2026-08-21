import React from "react";
import { Space, Tag } from "antd";
import { useTranslations } from "next-intl";

export interface IUserRole {
  role_id: number;
  name: string;
  description: string;
}

interface RoleTagListProps {
  roles?: IUserRole[];
}

export const RoleTagList: React.FC<RoleTagListProps> = ({ roles }) => {
  const t = useTranslations("RegisterType");
  const tu = useTranslations("Manage.User");

  // Xử lý trường hợp không có role nào
  if (!roles || roles.length === 0) {
    return <span style={{ color: "#999" }}>{tu("no_roles")}</span>;
  }

  const getRoleColor = (roleName: string) => {
    const name = roleName.toLowerCase();
    if (name.includes("admin")) return "gold";
    if (name.includes("manager") || name.includes("quản lý")) return "cyan";
    return "blue";
  };

  return (
    <Space wrap>
      {roles.map((role) => (
        <Tag key={role.role_id} color={getRoleColor(role.description)}>
          {t.has(role.name) ? t(role.name) : role.description}
        </Tag>
      ))}
    </Space>
  );
};
