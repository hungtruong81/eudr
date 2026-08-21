"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import {
  Table,
  Tag,
  Typography,
  Row,
  Col,
  Space,
} from "antd";
import { getProductLotByCode } from "../actions";
import BaseSheet from "@/components/shared/base-sheet";
import { formatDateDDMMYYYY } from "@/lib/utils";
import { getStatusColor, getStatusLabel } from "./product-lot-card";
import { useTranslations } from "next-intl";

const { Text, Title } = Typography;

interface IProductLotDetailProps {
  open: boolean;
  onClose: () => void;
  productLotCode: string | null;
}



const ProductLotDetail = ({
  open,
  onClose,
  productLotCode,
}: IProductLotDetailProps) => {
  const t = useTranslations("Factory");
  const ts = useTranslations("Status");
  const tc = useTranslations("Common");
  const tsa = useTranslations("Sales");
  const { data: productLot, isLoading } = useQuery({
    queryKey: ["product-lot", productLotCode],
    queryFn: () => getProductLotByCode(productLotCode || ""),
    enabled: !!productLotCode && open,
  });

  const detailColumns = React.useMemo(() => [
    {
      title: tc("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tc("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ], [tc]);

  const record = productLot?.data;

  const columns = [
    {
      title: t("lot_code"),
      dataIndex: "rubber_block_code",
      key: "rubber_block_code",
      render: (text: string) => <Text strong>{text.toUpperCase()}</Text>,
    },
    {
      title: tsa("product_type"),
      dataIndex: "product_type_name",
      key: "product_type_name",
    },
    {
      title: t("grade"),
      dataIndex: "grade_snapshot",
      key: "grade_snapshot",
    },
    {
      title: `${tsa("weight")} (kg)`,
      dataIndex: "weight_snapshot",
      key: "weight_snapshot",
      align: "right" as const,
      render: (val: string) => Number(val).toLocaleString(),
    },
  ];

  const statCardStyle: React.CSSProperties = {
    background: "rgba(255, 255, 255, 0.6)",
    backdropFilter: "blur(10px)",
    borderRadius: "12px",
    border: "1px solid rgba(0, 0, 0, 0.05)",
    padding: "16px",
    display: "flex",
    flexDirection: "column",
    alignItems: "center",
    justifyContent: "center",
    height: "100%",
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      title={t("lot_detail")}
      width={800}>
      {record ? (
        <Space orientation="vertical" size="large" style={{ width: "100%" }}>
          <Table
            dataSource={[
              {
                label: t("lot_code"),
                value: record.product_lot_code.toUpperCase(),
              },
              {
                label: tc("status"),
                value: (
                  <Tag color={getStatusColor(record.status)}>
                    {getStatusLabel(record.status, ts)}
                  </Tag>
                ),
              },
              { label: t("factory"), value: record.factory_name },
              {
                label: t("confirmed_at"),
                value: record.confirmed_at ? (
                  formatDateDDMMYYYY(record.confirmed_at)
                ) : (
                  <Text type="secondary">{t("not_confirmed")}</Text>
                ),
              },
              {
                label: t("production_date_from"),
                value: formatDateDDMMYYYY(record.production_date_from),
              },
              {
                label: t("production_date_to"),
                value: formatDateDDMMYYYY(record.production_date_to),
              },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
          />

          <Row gutter={16}>
            <Col span={12}>
              <div style={statCardStyle}>
                <Text type="secondary" style={{ fontSize: "12px" }}>
                  {t("total_blocks")}
                </Text>
                <Title level={3} style={{ margin: 0 }}>
                  {record.total_blocks}
                </Title>
              </div>
            </Col>
            <Col span={12}>
              <div style={statCardStyle}>
                <Text type="secondary" style={{ fontSize: "12px" }}>
                  {t("total_weight")}
                </Text>
                <Title level={3} style={{ margin: 0 }}>
                  {Number(record.total_weight).toLocaleString()}
                </Title>
              </div>
            </Col>
          </Row>

          <Title level={5} style={{ marginBottom: 8, marginTop: 16 }}>
            {t("rubber_block_list")}
          </Title>

          <Table
            dataSource={record.items}
            columns={columns}
            rowKey="product_lot_item_id"
            pagination={false}
            size="small"
            bordered
          />
        </Space>
      ) : (
        <div style={{ padding: "40px", textAlign: "center" }}>
          {isLoading ? tc("loading") : tc("not_found")}
        </div>
      )}
    </BaseSheet>
  );
};

export default ProductLotDetail;
