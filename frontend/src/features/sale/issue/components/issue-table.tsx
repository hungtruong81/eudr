import {
  CheckOutlined,
  CloseOutlined,
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
} from "@ant-design/icons";
import { Space, Tag, Modal, Tooltip } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { IIssue } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { CRUD } from "@/types/permission-context";
import { useTranslations } from "next-intl";

interface IssueTableProps {
  data: IIssue[] | undefined;
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  onEdit: (record: IIssue) => void;
  onDelete: (record: IIssue) => void;
  onCancel: (record: IIssue) => void;
  onView: (record: IIssue) => void;
  onConfirm: (record: IIssue) => void;
  deletingCode?: string | null;
  processingCode?: string | null;
  permissions: CRUD;
}

const IssueTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onEdit,
  onDelete,
  onCancel,
  onView,
  onConfirm,
  deletingCode,
  processingCode,
  permissions,
}: IssueTableProps) => {
  const t = useTranslations("Issue");
  const ts = useTranslations("Status");
  const tc = useTranslations("Common");
  const columns: CustomColumnTypeTable<IIssue>[] = [
    {
      title: t("issue_code"),
      dataIndex: "issue_code",
      fixed: "left",
      width: 150,
      render: (val) => <span className="font-bold uppercase">{val}</span>,
    },
    {
      title: t("issue_date"),
      dataIndex: "issue_date",
      width: 160,
      render: (val) => (val ? new Date(val).toLocaleString("vi-VN") : "-"),
    },
    {
      title: t("sale_order"),
      dataIndex: "sale_order_id",
      width: 140,
      render: (val) => `#${val}`,
    },
    {
      title: tc("company"),
      dataIndex: "company_id",
      width: 120,
      render: (val) => `#${val}`,
    },
    {
      title: tc("warehouse") || "Warehouse",
      dataIndex: "warehouse_id",
      width: 120,
      render: (val) => (val ? `#${val}` : "-"),
    },
    {
      title: tc("transport") || "Transport",
      width: 200,
      render: (_, record) => (
        <div>
          <div>{record.vehicle_no || "-"}</div>
          <div className="text-xs text-gray-400">{record.shipper || "-"}</div>
        </div>
      ),
    },
    {
      title: t("receiver"),
      dataIndex: "receiver",
      width: 150,
      render: (val) => val || "-",
    },
    {
      title: t("document_ref"),
      dataIndex: "document_ref",
      width: 160,
      render: (val) => val || "-",
    },
    {
      title: t("reason"),
      dataIndex: "reason_code",
      width: 140,
      render: (val) => val || "-",
    },
    {
      title: tc("notes"),
      dataIndex: "notes",
      width: 200,
      render: (val) => (
        <Tooltip title={val}>
          <span className="line-clamp-1">{val || "-"}</span>
        </Tooltip>
      ),
    },
    {
      title: tc("status"),
      dataIndex: "status",
      width: 130,
      render: (status: string) => {
        const text = ts(status);
        let color = "default";
        switch (status) {
          case "draft":
            color = "blue";
            break;
          case "cancelled":
            color = "red";
            break;
          case "issued":
            color = "green";
            break;
        }

        return <Tag color={color}>{text}</Tag>;
      },
    },
    {
      title: tc("created_at"),
      dataIndex: "created_at",
      width: 160,
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      fixed: "right",
      width: 150,
      align: "center",
      render: (_, record) => (
        <Space>
          <TooltipButton
            tooltip={tc("view_detail")}
            icon={<EyeOutlined />}
            type="dashed"
            onClick={() => onView(record)}
          />
          {record.status === "draft" && (
            <>
              {(permissions.full || permissions.update) && (
                <ConfirmTooltipButton
                  confirmTitle={tc("confirm")}
                  confirmDescription={t("confirm_confirm_description", {
                    code: record.issue_code?.toUpperCase(),
                  })}
                  tooltip={tc("confirm")}
                  icon={<CheckOutlined />}
                  onConfirm={() => onConfirm(record)}
                  type="primary"
                  loading={processingCode === record.issue_code}
                />
              )}
              {(permissions.full || permissions.update) && (
                <ConfirmTooltipButton
                  confirmTitle={t("confirm_cancel")}
                  confirmDescription={t("confirm_cancel_description", {
                    code: record.issue_code?.toUpperCase(),
                  })}
                  tooltip={tc("cancel")}
                  danger
                  icon={<CloseOutlined />}
                  onConfirm={() => onCancel(record)}
                  loading={processingCode === record.issue_code}
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
            </>
          )}
          {(permissions.full || permissions.delete) && (
            <ConfirmTooltipButton
              confirmTitle={tc("confirm_delete")}
              confirmDescription={t("confirm_delete_description", {
                code: record.issue_code?.toUpperCase(),
              })}
              tooltip={tc("delete")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => onDelete(record)}
              loading={deletingCode === record.issue_code}
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<IIssue>
      rowKey="issue_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="issue-table"
      pagination={{
        current: pagination?.current || 1,
        pageSize: pagination?.pageSize || 10,
        total: pagination?.total || 0,
        showSizeChanger: true,
      }}
      onChange={(pagination) => {
        onPageChange(pagination.current || 1, pagination.pageSize || 10);
      }}
      scroll={{ x: 1200 }}
    />
  );
};

export default IssueTable;
