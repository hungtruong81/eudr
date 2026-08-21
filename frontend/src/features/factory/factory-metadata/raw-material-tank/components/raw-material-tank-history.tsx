"use client";
import { useQuery } from "@tanstack/react-query";
import { Modal, Space, Tag, Typography } from "antd";
import React, { useState } from "react";
import { CommonPaginationParams } from "@/types/api";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { historyRawMaterialTank } from "../actions";
import { IHistoryRawMaterialTank } from "../types";
import { useTranslations } from "next-intl";
import AppModal from "@/components/modal";

const { Text } = Typography;

interface Props {
  rawMaterialTankCode: string;
  open: boolean;
  onClose: () => void;
}

const RawMaterialTankHistory: React.FC<Props> = ({
  rawMaterialTankCode,
  open,
  onClose,
}) => {
  const t = useTranslations("Factory.metadata.raw_material_tank");
  const tc = useTranslations("Common");
  const ts = useTranslations("Status");

  const [params, setParams] = useState<CommonPaginationParams>({
    page: 1,
    limit: 10,
  });

  const { data, isLoading } = useQuery({
    queryKey: ["raw-material-tank-history", rawMaterialTankCode, params],
    queryFn: () => historyRawMaterialTank(rawMaterialTankCode, params),
    enabled: !!rawMaterialTankCode && open,
  });

  const columns: CustomColumnTypeTable<IHistoryRawMaterialTank>[] = [
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
      title: t("rubber_type"),
      dataIndex: "rubber_type",
      render: (type) => {
        const types: Record<string, string> = {
          latex: t("latex"),
          scrap_rubber: t("scrap_rubber"),
          mixed: t("mixed"),
        };
        return types[type] || type;
      },
    },
    {
      title: t("weight_kg"),
      dataIndex: "weight",
      render: (weight: string) => <Text strong>{weight}</Text>,
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
        <CustomTable<IHistoryRawMaterialTank>
          rowKey="raw_material_tank_history_id"
          columns={columns}
          dataSource={data?.data?.records || []}
          loading={isLoading}
          tableId="raw-material-tank-history-table"
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

export default RawMaterialTankHistory;
