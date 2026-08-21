import {
  CloseOutlined,
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
} from "@ant-design/icons";
import { Space, Tag, Tooltip } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { IPallet } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { useTranslations } from "next-intl";
import { usePermissions } from "@/contexts/permission-context";

interface PalletTableProps {
  data: IPallet[] | undefined;
  loading?: boolean;
  factories: any[] | undefined;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  onEdit: (record: IPallet) => void;
  onDelete: (record: IPallet) => void;
  onCancel: (record: IPallet) => void;
  onView: (record: IPallet) => void;
  deletingCode?: string | null;
  processingCode?: string | null;
}

const PalletTable = ({
  data,
  loading,
  factories,
  pagination,
  onPageChange,
  onEdit,
  onDelete,
  onCancel,
  onView,
  deletingCode,
  processingCode,
}: PalletTableProps) => {
  const t = useTranslations("Pallet");
  const tc = useTranslations("Common");
  const { trader } = usePermissions();

  // Create a map of factory_id -> factory_name
  const factoryMap = React.useMemo(() => {
    const map = new Map<number, string>();
    factories?.forEach((f) => {
      map.set(f.factory_id, f.factory_name);
    });
    return map;
  }, [factories]);

  const columns: CustomColumnTypeTable<IPallet>[] = [
    {
      title: t("pallet_code"),
      dataIndex: "pallet_code",
      fixed: "left",
      width: 150,
      render: (val) => <span className="font-bold uppercase">{val}</span>,
    },
    // {
    //   title: t("warehouse"),
    //   dataIndex: "warehouse_id",
    //   width: 200,
    //   render: (val) => factoryMap.get(Number(val)) || `#${val}`,
    // },
    {
      title: t("total_bales"),
      dataIndex: "total_bales",
      width: 120,
      align: "right",
      render: (val) =>
        typeof val === "number" ? val.toLocaleString("vi-VN") : "0",
    },
    {
      title: t("total_weight"),
      dataIndex: "total_weight",
      width: 150,
      align: "right",
      render: (val) =>
        typeof val === "number" || typeof val === "string"
          ? `${Number(val).toLocaleString("vi-VN")} kg`
          : "0 kg",
    },
    {
      title: t("status"),
      dataIndex: "status",
      width: 130,
      render: (status: string) => {
        const text = t(`status_${status}`) || status;
        let color = "default";
        switch (status) {
          case "draft":
            color = "blue";
            break;
          case "packed":
            color = "green";
            break;
          case "shipped":
            color = "purple";
            break;
          case "cancelled":
            color = "red";
            break;
        }

        return <Tag color={color}>{text}</Tag>;
      },
    },
    {
      title: t("packed_at"),
      dataIndex: "packed_at",
      width: 160,
      render: (val) => (val ? new Date(val).toLocaleString("vi-VN") : "-"),
    },
    {
      title: t("shipped_at"),
      dataIndex: "shipped_at",
      width: 160,
      render: (val) => (val ? new Date(val).toLocaleString("vi-VN") : "-"),
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
      width: 160,
      align: "center",
      render: (_, record) => {
        const isDraft = record.status === "draft";
        const isCancelable =
          record.status === "draft" || record.status === "empty";

        const canView =
          trader.pallet.view.self ||
          trader.pallet.view.own ||
          trader.pallet.view.all ||
          trader.pallet.full;

        const canUpdate =
          trader.pallet.update.self ||
          trader.pallet.update.own ||
          trader.pallet.full;

        const canCancel =
          trader.pallet.cancel.self ||
          trader.pallet.cancel.own ||
          trader.pallet.full;

        const canDelete =
          trader.pallet.delete.self ||
          trader.pallet.delete.own ||
          trader.pallet.full;

        return (
          <Space>
            {canView && (
              <TooltipButton
                tooltip={tc("view_detail")}
                icon={<EyeOutlined />}
                type="dashed"
                onClick={() => onView(record)}
              />
            )}
            {canUpdate && (
              <TooltipButton
                tooltip={tc("edit")}
                type="primary"
                ghost
                icon={<EditOutlined />}
                onClick={() => onEdit(record)}
              />
            )}
            {canCancel && isCancelable && (
              <ConfirmTooltipButton
                confirmTitle={t("confirm_cancel_title")}
                confirmDescription={t("confirm_cancel_desc", {
                  code: record.pallet_code.toUpperCase(),
                })}
                tooltip={tc("cancel")}
                danger
                icon={<CloseOutlined />}
                onConfirm={() => onCancel(record)}
                loading={processingCode === record.pallet_code}
              />
            )}
            {canDelete && (
              <ConfirmTooltipButton
                confirmTitle={t("confirm_delete_title")}
                confirmDescription={t("confirm_delete_desc", {
                  code: record.pallet_code.toUpperCase(),
                })}
                tooltip={tc("delete")}
                danger
                icon={<DeleteOutlined />}
                onConfirm={() => onDelete(record)}
                loading={deletingCode === record.pallet_code}
              />
            )}
          </Space>
        );
      },
    },
  ];

  return (
    <CustomTable<IPallet>
      rowKey="pallet_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="pallet-table"
      pagination={{
        current: pagination?.current || 1,
        pageSize: pagination?.pageSize || 10,
        total: pagination?.total || 0,
        showSizeChanger: true,
      }}
      onChange={(pagination) => {
        onPageChange(pagination.current || 1, pagination.pageSize || 10);
      }}
      scroll={{ x: 1400 }}
    />
  );
};

export default PalletTable;
