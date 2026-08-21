import {
  DeleteOutlined,
  EditOutlined,
  DollarOutlined,
  LineChartOutlined,
} from "@ant-design/icons";
import { Space, Tag, Typography } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { IGrade } from "../type";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { CRUD } from "@/types/permission-context";
import { useTranslations } from "next-intl";
import dayjs from "dayjs";

interface GradeTableProps {
  data: IGrade[] | undefined;
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  onEdit: (record: IGrade) => void;
  onDelete: (record: IGrade) => void;
  onUpdatePrice: (record: IGrade) => void;
  onViewHistory: (record: IGrade) => void;
  deletingCode?: string | null;
  permissions: CRUD;
}

const GradeTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onEdit,
  onDelete,
  onUpdatePrice,
  onViewHistory,
  deletingCode,
  permissions,
}: GradeTableProps) => {
  const t = useTranslations("Manage.Grade");
  const tc = useTranslations("Common");
  console.log(permissions);
  const columns: CustomColumnTypeTable<IGrade>[] = [
    // {
    //   title: t("grade_code"),
    //   dataIndex: "grade_code",
    //   fixed: "left",
    //   width: 150,
    //   render: (val) => <span className="font-bold uppercase">{val}</span>,
    // },
    {
      title: t("grade_name"),
      dataIndex: "name",
      width: 200,
    },
    {
      title: t("current_domestic_price"),
      dataIndex: "current_domestic_price",
      width: 200,
      render: (val) => (val ? `${val.toLocaleString()} VNĐ` : "-"),
    },
    {
      title: t("current_international_price"),
      dataIndex: "current_international_price",
      width: 200,
      render: (val) => (val ? `$${val.toLocaleString()}` : "-"),
    },
    // {
    //   title: t("current_price_effective_from"),
    //   dataIndex: "current_price_effective_from",
    //   width: 150,
    //   render: (val) => (val ? dayjs(val).format("DD/MM/YYYY") : "-"),
    // },
    // {
    //   title: t("current_price_effective_to"),
    //   dataIndex: "current_price_effective_to",
    //   width: 150,
    //   render: (val) => (val ? dayjs(val).format("DD/MM/YYYY") : "-"),
    // },
    {
      title: tc("notes"),
      dataIndex: "description",
      width: 150,
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      fixed: "right",
      align: "center",
      width: 180,
      render: (_, record) => (
        <Space>
          {(permissions.full || permissions.update.all) && (
            <TooltipButton
              tooltip={t("update_price")}
              type="default"
              icon={<DollarOutlined />}
              onClick={() => onUpdatePrice(record)}
            />
          )}
          <TooltipButton
            tooltip={t("view_price_history")}
            type="default"
            icon={<LineChartOutlined />}
            onClick={() => onViewHistory(record)}
          />
          {(permissions.full || permissions.update.all) && (
            <TooltipButton
              tooltip={tc("edit")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => onEdit(record)}
            />
          )}
          {(permissions.full || permissions.delete.all) && (
            <ConfirmTooltipButton
              confirmTitle={tc("delete_confirm_title")}
              confirmDescription={t("delete_confirm", {
                name: record.name,
              })}
              tooltip={tc("delete")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => onDelete(record)}
              loading={deletingCode === record.grade_code}
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<IGrade>
      rowKey="grade_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="grade-table"
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

export default GradeTable;
