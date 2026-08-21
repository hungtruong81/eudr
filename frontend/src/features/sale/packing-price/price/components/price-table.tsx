import { DeleteOutlined, EditOutlined } from "@ant-design/icons";
import { Space, Tag } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { IPrice } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { useTranslations } from "next-intl";
import { usePermissions } from "@/contexts/permission-context";

interface PriceTableProps {
  data: IPrice[] | undefined;
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  onEdit: (record: IPrice) => void;
  onDelete: (record: IPrice) => void;
  deletingCode?: string | null;
}

const PriceTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onEdit,
  onDelete,
  deletingCode,
}: PriceTableProps) => {
  const t = useTranslations("Pallet");
  const tc = useTranslations("Common");
  const { trader } = usePermissions();

  const columns: CustomColumnTypeTable<IPrice>[] = [
    {
      title: t("price_code"),
      dataIndex: "price_code",
      fixed: "left",
      width: 150,
      render: (val) => <span className="font-bold uppercase">{val}</span>,
    },
    {
      title: t("price_name"),
      dataIndex: "price_name",
      width: 200,
    },
    {
      title: t("price_type"),
      dataIndex: "price_type",
      width: 150,
      render: (type: string) => {
        // let text = type;
        // let color = "default";
        // if (type === "pallet") {
        //   text = t("type_pallet");
        //   color = "blue";
        // } else if (type === "box") {
        //   text = t("type_box");
        //   color = "cyan";
        // } else {
        //   text = t("type_other") + ` (${type})`;
        //   color = "orange";
        // }
        return <Tag color={"processing"}>{type}</Tag>;
      },
    },
    {
      title: t("domestic_price"),
      dataIndex: "domestic_price",
      width: 160,
      align: "right",
      render: (val) =>
        typeof val === "number"
          ? `${val.toLocaleString("vi-VN")} đ`
          : `${val} đ`,
    },
    {
      title: t("international_price"),
      dataIndex: "international_price",
      width: 160,
      align: "right",
      render: (val) =>
        typeof val === "number" ? `$${val.toLocaleString("en-US")}` : `$${val}`,
    },
    {
      title: t("created_at"),
      dataIndex: "created_at",
      width: 160,
      render: (val) => (val ? new Date(val).toLocaleString("vi-VN") : "-"),
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      fixed: "right",
      width: 120,
      align: "center",
      render: (_, record) => {
        const canUpdate =
          trader.price.update.own ||
          trader.price.update.all ||
          trader.price.full;

        const canDelete =
          trader.price.delete.own ||
          trader.price.delete.all ||
          trader.price.full;

        return (
          <Space>
            {canUpdate && (
              <TooltipButton
                tooltip={tc("edit")}
                type="primary"
                ghost
                icon={<EditOutlined />}
                onClick={() => onEdit(record)}
              />
            )}
            {canDelete && (
              <ConfirmTooltipButton
                confirmTitle={t("confirm_delete_price_title")}
                confirmDescription={t("confirm_delete_price_desc", {
                  name: record.price_name,
                })}
                tooltip={tc("delete")}
                danger
                icon={<DeleteOutlined />}
                onConfirm={() => onDelete(record)}
                loading={deletingCode === record.price_code}
              />
            )}
          </Space>
        );
      },
    },
  ];

  return (
    <CustomTable<IPrice>
      rowKey="price_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="price-table"
      pagination={{
        current: pagination?.current || 1,
        pageSize: pagination?.pageSize || 10,
        total: pagination?.total || 0,
        showSizeChanger: true,
      }}
      onChange={(pagination) => {
        onPageChange(pagination.current || 1, pagination.pageSize || 10);
      }}
      scroll={{ x: 1000 }}
    />
  );
};

export default PriceTable;
