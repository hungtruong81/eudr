import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { DeleteOutlined, EditOutlined, EyeOutlined } from "@ant-design/icons";
import { Space, Tag } from "antd";
import React from "react";
import { IRawMaterialRelease } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { useTranslations } from "next-intl";

interface IRawMaterialReleaseTableProps {
  dataSource: IRawMaterialRelease[];
  loading: boolean;
  onEdit: (record: IRawMaterialRelease) => void;
  onDelete: (record: IRawMaterialRelease) => void;
  onView: (record: IRawMaterialRelease) => void;
  pagination: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPaginationChange: (page: number, pageSize: number) => void;
  deletingId?: string | number;
}

const RawMaterialReleaseTable = ({
  dataSource,
  loading,
  onEdit,
  onDelete,
  onView,
  pagination,
  onPaginationChange,
  deletingId,
}: IRawMaterialReleaseTableProps) => {
  const t = useTranslations("Factory.material_release");
  const tc = useTranslations("Common");
  const tf = useTranslations("Factory.fg_receipt");

  const columns: CustomColumnTypeTable<IRawMaterialRelease>[] = [
    {
      title: t("code"),
      dataIndex: "material_release_code",
      fixed: "left",
    },
    {
      title: t("name"),
      dataIndex: "material_release_name",
    },
    {
      title: t("order_code"),
      dataIndex: "production_order_code",
      render: (code, record) => code || "N/A",
    },
    {
      title: tc("total_weight_kg"),
      dataIndex: "total_requested_weight",
      type: "number",
    },
    {
      title: tc("status"),
      dataIndex: "status",
      render: (status: string) => {
        let color = "default";
        let label = status;
        switch (status) {
          case "draft":
            color = "blue";
            label = tf("draft");
            break;
          case "releasing":
            color = "warning";
            label = t("releasing");
            break;
          case "completed":
            color = "success";
            label = tc("completed");
            break;
        }
        return <Tag color={color}>{label}</Tag>;
      },
    },
    {
      title: tc("created_at"),
      dataIndex: "created_at",
      type: "date",
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      fixed: "right",
      width: 120,
      render: (_, record) => (
        <Space>
          <TooltipButton
            tooltip={t("view_tooltip")}
            icon={<EyeOutlined />}
            onClick={() => onView(record)}
          />
          <TooltipButton
            tooltip={t("edit_tooltip")}
            type="primary"
            ghost
            icon={<EditOutlined />}
            onClick={() => onEdit(record)}
          />
          <ConfirmTooltipButton
            confirmTitle={t("confirm_delete_title")}
            confirmDescription={t("confirm_delete_desc")}
            tooltip={tc("delete")}
            danger
            icon={<DeleteOutlined />}
            onConfirm={() => onDelete(record)}
            loading={deletingId === record.material_release_code}
          />
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<IRawMaterialRelease>
      rowKey="material_release_id"
      columns={columns}
      dataSource={dataSource}
      loading={loading}
      pagination={pagination}
      tableId="raw-material-release-table"
      onChange={(p) => onPaginationChange(p.current || 1, p.pageSize || 10)}
      scroll={{ x: 1200 }}
    />
  );
};

export default RawMaterialReleaseTable;
