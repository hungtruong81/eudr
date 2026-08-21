"use client";
import { useQuery } from "@tanstack/react-query";
import { Modal, Space, Tag, Typography } from "antd";
import React, { useState } from "react";
import { CommonPaginationParams } from "@/types/api";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { historyProductTank } from "../actions";
import { IProductTankHistory } from "../types";
import { useTranslations } from "next-intl";
import AppModal from "@/components/modal";

const { Text } = Typography;

interface Props {
  productTankCode: string;
  open: boolean;
  onClose: () => void;
}

const ProductTankHistory: React.FC<Props> = ({
  productTankCode,
  open,
  onClose,
}) => {
  const t = useTranslations("Factory.metadata.product_tank");
  const tc = useTranslations("Common");
  const ts = useTranslations("Status");

  const [params, setParams] = useState<CommonPaginationParams>({
    page: 1,
    limit: 10,
  });

  const { data, isLoading } = useQuery({
    queryKey: ["product-tank-history", productTankCode, params],
    queryFn: () => historyProductTank(productTankCode, params),
    enabled: !!productTankCode && open,
  });

  const columns: CustomColumnTypeTable<IProductTankHistory>[] = [
    {
      title: t("action_type"),
      dataIndex: "action_type",
      render: (action_type: string) => {
        let color = "blue";
        let label = action_type || "N/A";

        if (action_type === "input") {
          color = "green";
          label = ts("input");
        }
        if (action_type === "output") {
          color = "volcano";
          label = ts("output");
        }

        return <Tag color={color}>{label}</Tag>;
      },
    },
    {
      title: t("category"),
      dataIndex: "product_type_category",
    },
    {
      title: t("quantity"),
      dataIndex: "quantity",
      render: (qty: number) => <Text strong>{qty}</Text>,
    },
    {
      title: t("weight_kg"),
      dataIndex: "weight",
      render: (weight: number) => <Text strong>{weight}</Text>,
    },
    {
      title: t("volume_transition"),
      key: "volume",
      render: (_, record) => (
        <Space size="small">
          <Text type="secondary">{record.volume_before || 0}</Text>
          <Text type="secondary">→</Text>
          <Text strong>{record.volume_after || 0}</Text>
        </Space>
      ),
    },
    {
      title: tc("created_at"),
      dataIndex: "created_at",
      type: "date",
    },
    {
      title: tc("notes"),
      dataIndex: "notes",
      render: (notes: string) =>
        notes || <Text type="secondary">{tc("none")}</Text>,
    },
  ];

  return (
    <AppModal
      title={t("history_title")}
      open={open}
      onCancel={onClose}
      footer={null}
      width={1200}>
      <Space orientation="vertical" style={{ width: "100%" }}>
        <CustomTable<IProductTankHistory>
          rowKey="product_tank_history_id"
          columns={columns}
          dataSource={data?.data?.records || []}
          loading={isLoading}
          tableId="product-tank-history-table"
          pagination={{
            current: data?.data?.current_page || 1,
            pageSize: data?.data?.page_limit || 10,
            total: data?.data?.total_records || 0,
          }}
          onChange={(pagination) => {
            setParams({
              ...params,
              page: pagination.current || 1,
              limit: pagination.pageSize || 10,
            });
          }}
          scroll={{
            x: "max-content",
          }}
        />
      </Space>
    </AppModal>
  );
};

export default ProductTankHistory;
