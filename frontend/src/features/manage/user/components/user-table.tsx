"use client";

import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import CustomTable from "@/components/custom-table";
import { RoleTagList } from "@/components/role-tag";
import { TooltipButton } from "@/components/tooltip-button";
import { CRUD } from "@/types/permission-context";
import {
  DeleteOutlined,
  EditOutlined,
  SafetyCertificateOutlined,
} from "@ant-design/icons";
import { Space, Switch, Typography } from "antd";
import type { ColumnsType } from "antd/es/table";
import dayjs from "dayjs";
import { IUserCompany, IUserRole } from "../types";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface UserTableProps {
  data: IUserCompany[];
  loading: boolean;
  pagination: any;
  onEdit: (record: IUserCompany) => void;
  onDelete: (record: IUserCompany) => void;
  onAssignRoles: (record: IUserCompany) => void;
  onToggleActive: (record: IUserCompany) => void;
  permissions: CRUD;
}

const UserTable = ({
  data,
  loading,
  pagination,
  onEdit,
  onDelete,
  onAssignRoles,
  onToggleActive,
  permissions,
}: UserTableProps) => {
  const t = useTranslations("Manage.User");
  const tc = useTranslations("Common");

  const columns: ColumnsType<IUserCompany> = [
    {
      title: t("full_name"),
      dataIndex: "full_name",
      key: "full_name",
      render: (text, record) => (
        <Space orientation="vertical" size={0}>
          <Text strong>{text}</Text>
          <Text type="secondary" style={{ fontSize: 12 }}>
            {record.user_code}
          </Text>
        </Space>
      ),
    },
    {
      title: t("phone"),
      dataIndex: "phone",
      key: "phone",
    },
    {
      title: t("email"),
      dataIndex: "email",
      key: "email",
    },
    {
      title: t("role"),
      dataIndex: "user_roles",
      key: "user_roles",
      render: (roles: IUserRole[]) => <RoleTagList roles={roles} />,
    },
    {
      title: tc("company"),
      dataIndex: "company_name",
      key: "company_name",
      render: (text, record) => (
        <Space orientation="vertical" size={0}>
          <Text>{text}</Text>
          <Text type="secondary" style={{ fontSize: 11 }}>
            {record.company_code}
          </Text>
        </Space>
      ),
    },
    {
      title: tc("created_at"),
      dataIndex: "created_at",
      key: "created_at",
      render: (date: string) =>
        date ? dayjs(date).format("DD/MM/YYYY HH:mm") : "-",
    },
    {
      title: tc("actions"),
      key: "actions",
      fixed: "right",
      render: (_, record) => (
        <Space>
          {(permissions.full || permissions.update) && (
            <TooltipButton
              tooltip={t("assign_roles")}
              icon={<SafetyCertificateOutlined />}
              onClick={() => onAssignRoles(record)}
            />
          )}
          {(permissions.full || permissions.update) && (
            <TooltipButton
              tooltip={tc("edit")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => onEdit(record)}
            />
          )}
          {(permissions.full || permissions.update) && (
            <Switch
              checked={record.is_active === 1}
              onChange={() => onToggleActive(record)}
            />
          )}
          {(permissions.full || permissions.delete) && (
            <ConfirmTooltipButton
              confirmTitle={tc("delete_confirm_title")}
              confirmDescription={t("delete_confirm", {
                name: record.full_name,
              })}
              tooltip={tc("delete")}
              type="primary"
              danger
              ghost
              icon={<DeleteOutlined />}
              onConfirm={() => onDelete(record)}
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<IUserCompany>
      tableId="user-table"
      columns={columns}
      dataSource={data}
      loading={loading}
      pagination={pagination}
      rowKey="user_id"
      scroll={{ x: "max-content" }}
    />
  );
};

export default UserTable;
