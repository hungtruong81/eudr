import {
  DeleteOutlined,
  EditOutlined,
  TeamOutlined,
  LockOutlined,
} from "@ant-design/icons";
import { Space, Tag, Modal, Tooltip } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { ICompanyGroup } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { CRUD } from "@/types/permission-context";
import { useTranslations } from "next-intl";

interface GroupTableProps {
  data: ICompanyGroup[] | undefined;
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  onEdit: (record: ICompanyGroup) => void;
  onDelete: (record: ICompanyGroup) => void;
  onAssignMembers: (record: ICompanyGroup) => void;
  onAssignPermissions: (record: ICompanyGroup) => void;
  deletingCode?: string | null;
  permissions: CRUD;
}

const GroupTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onEdit,
  onDelete,
  onAssignMembers,
  onAssignPermissions,
  deletingCode,
  permissions,
}: GroupTableProps) => {
  const t = useTranslations("Manage.GroupPermission");
  const tc = useTranslations("Common");

  const columns: CustomColumnTypeTable<ICompanyGroup>[] = [
    {
      title: t("group_code"),
      dataIndex: "company_group_code",
      render: (val) => <span className="font-bold uppercase">{val}</span>,
    },
    {
      title: t("group_name"),
      dataIndex: "name",
    },
    {
      title: tc("company"),
      dataIndex: "company_name",
      render: (val, record) => (
        <div>
          <div>{val}</div>
          <div className="text-xs text-gray-400">
            {record.company_short_name}
          </div>
        </div>
      ),
    },
    {
      title: t("member_count"),
      dataIndex: "member_count",
      align: "center",
      render: (val) => val || 0,
    },
    {
      title: t("description"),
      dataIndex: "description",
      ellipsis: true,
    },
    {
      title: tc("status"),
      dataIndex: "status",
      render: (status: string) => {
        const color = status === "active" ? "green" : "red";
        return (
          <Tag color={color}>
            {status === "active" ? tc("status_active") : tc("status_inactive")}
          </Tag>
        );
      },
    },
    {
      title: "Hành động",
      dataIndex: "actions",
      fixed: "right",
      width: 200,
      align: "center",
      render: (_, record) => (
        <Space>
          {(permissions.full || permissions.update) && (
            <TooltipButton
              tooltip={t("assign_permissions")}
              icon={<LockOutlined />}
              onClick={() => onAssignPermissions(record)}
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
          {(permissions.full || permissions.delete) && (
            <ConfirmTooltipButton
              confirmTitle={tc("delete_confirm_title")}
              confirmDescription={t("delete_confirm", { name: record.name })}
              tooltip={tc("delete")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => onDelete(record)}
              loading={deletingCode === record.company_group_code}
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<ICompanyGroup>
      rowKey="company_group_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="group-table"
      pagination={{
        current: pagination?.current || 1,
        pageSize: pagination?.pageSize || 10,
        total: pagination?.total || 0,
        showSizeChanger: true,
      }}
      onChange={(pagination) => {
        onPageChange(pagination.current || 1, pagination.pageSize || 10);
      }}
      scroll={{ x: "max-content" }}
    />
  );
};

export default GroupTable;
