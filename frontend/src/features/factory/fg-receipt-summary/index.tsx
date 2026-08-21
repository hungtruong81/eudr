"use client";
import React, { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  Table,
  Select,
  Card,
  Typography,
  Flex,
  Space,
  Empty,
  Form,
  Tag,
  DatePicker,
} from "antd";
import { DatabaseOutlined } from "@ant-design/icons";
import { BaseFilter } from "@/components/base-filter";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getFgReceiptSummary, IGetFgReceiptSummaryParams } from "./actions";
import { getProductionOrders } from "../manage-order-ticket/product-order/actions";
import { getProductTank } from "../factory-metadata/product-tank/actions";
import { getProductTypes } from "../factory-metadata/product-type/action";
import { IProductionOrder } from "../manage-order-ticket/product-order/types";
import { IProductTank } from "../factory-metadata/product-tank/types";
import { IFgReceiptSummary } from "./types";
import { CustomColumnTypeTable } from "@/components/custom-table";
import dayjs from "dayjs";
import { useTranslations } from "next-intl";

const { Title, Text } = Typography;

const FgReceiptSummary = () => {
  const t = useTranslations("Factory.fg_summary");
  const tc = useTranslations("Common");
  const tf = useTranslations("Factory.fg_receipt");

  const [form] = Form.useForm();
  const [params, setParams] = useState<IGetFgReceiptSummaryParams>({
    page: 1,
    limit: 10,
  });
  const category = Form.useWatch("product_type_category", form);

  const { data, isFetching } = useQuery({
    queryKey: ["fg-receipt-summary", params],
    queryFn: () => getFgReceiptSummary(params),
  });

  const handleSearch = (values: any) => {
    const { production_dates, ...rest } = values;
    setParams((prev) => ({
      ...prev,
      ...rest,
      production_date_from: production_dates?.[0]?.format("YYYY-MM-DD"),
      production_date_to: production_dates?.[1]?.format("YYYY-MM-DD"),
      production_order_id: values.production_order_id
        ? Number(values.production_order_id)
        : undefined,
      product_tank_id: values.product_tank_id
        ? Number(values.product_tank_id)
        : undefined,
      product_type_id: values.product_type_id
        ? Number(values.product_type_id)
        : undefined,
      page: 1,
    }));
  };

  const handleReset = () => {
    setParams({
      page: 1,
      limit: 10,
    });
    form.resetFields();
  };

  const columns: CustomColumnTypeTable<IFgReceiptSummary>[] = [
    {
      title: t("block_code"),
      dataIndex: "rubber_block_code",
      key: "rubber_block_code",
      render: (value) => value?.toUpperCase(),
    },
    {
      title: t("production_order_name"),
      dataIndex: "production_order_name",
      key: "production_order_name",
    },

    {
      title: t("product_name"),
      dataIndex: "product_type_name",
      key: "product_type_name",
    },

    {
      title: t("grade"),
      dataIndex: "grade",
      key: "grade",
      render: (value) => <Tag color="blue">{value}</Tag>,
    },
    {
      title: tc("weight_kg"),
      dataIndex: "weight",
      key: "weight",
      align: "right",
      render: (value) => (value ? Number(value).toLocaleString() : "0"),
    },

    {
      title: t("production_date"),
      dataIndex: "production_date",
      key: "production_date",
      render: (value) => (value ? dayjs(value).format("DD/MM/YYYY") : "-"),
    },
    {
      title: tc("created_at"),
      dataIndex: "created_at",
      key: "created_at",
      render: (value) =>
        value ? dayjs(value).format("DD/MM/YYYY HH:mm:ss") : "-",
    },
    {
      title: tc("updated_at"),
      dataIndex: "updated_at",
      key: "updated_at",
      render: (value) =>
        value ? dayjs(value).format("DD/MM/YYYY HH:mm:ss") : "-",
    },

    {
      title: tc("status"),
      dataIndex: "status",
      key: "status",
      render: (status: string) => {
        const map = {
          available: { label: t("available"), color: "green" },
          allocated: { label: t("allocated"), color: "blue" },
          shipped: { label: t("shipped"), color: "purple" },
          defective: { label: t("defective"), color: "red" },
        };

        const item = map[status as keyof typeof map];

        return item ? (
          <Tag color={item.color}>{item.label}</Tag>
        ) : (
          <Tag>{status}</Tag>
        );
      },
    },
  ];
  return (
    <Space orientation="vertical" style={{ width: "100%" }} size={24}>
      <Card>
        <BaseFilter
          form={form}
          onFinish={handleSearch}
          onReset={handleReset}
          loading={isFetching}>
          <Form.Item
            name="production_order_id"
            style={{ minWidth: 220 }}
            label={tf("production_order")}>
            <InfiniteScrollSelect<IProductionOrder>
              queryKey={["production-orders-summary"]}
              fetchFn={getProductionOrders}
              mapOption={(item) => ({
                label: item.production_order_name,
                value: String(item.production_order_id),
              })}
              placeholder={tf("select_production_order")}
              allowClear
            />
          </Form.Item>

          <Form.Item
            name="product_type_category"
            style={{ minWidth: 160 }}
            label={tc("category")}>
            <Select
              placeholder={tc("category")}
              allowClear
              options={[
                { label: tc("latex"), value: "concentrated_latex" },
                { label: tc("scrap_rubber"), value: "scrap_rubber" },
              ]}
              onChange={() => {
                form.setFieldValue("product_type_id", undefined);
              }}
            />
          </Form.Item>

          <Form.Item
            name="product_type_id"
            style={{ minWidth: 220 }}
            label={tc("product")}>
            <InfiniteScrollSelect
              queryKey={["product-types-summary", category]}
              fetchFn={(p) =>
                getProductTypes({ ...p, product_type_category: category })
              }
              mapOption={(item: any) => ({
                label: item.product_type_name,
                value: String(item.product_type_id),
              })}
              placeholder={tf("select_product")}
              disabled={!category}
              allowClear
            />
          </Form.Item>

          <Form.Item
            name="product_tank_id"
            style={{ minWidth: 200 }}
            label={tc("tank_name")}>
            <InfiniteScrollSelect<IProductTank>
              queryKey={["product-tanks-summary"]}
              fetchFn={getProductTank}
              mapOption={(item) => ({
                label: item.product_tank_name,
                value: String(item.product_tank_id),
              })}
              placeholder={tf("select_tank")}
              allowClear
            />
          </Form.Item>

          <Form.Item
            name="production_dates"
            style={{ minWidth: 250 }}
            label={t("production_date")}>
            <DatePicker.RangePicker
              placeholder={[tc("from_date"), tc("to_date")]}
              format="DD/MM/YYYY"
              allowClear
            />
          </Form.Item>

          <Form.Item
            name="status"
            style={{ minWidth: 160 }}
            label={tc("status")}>
            <Select
              placeholder={tc("status")}
              allowClear
              options={[
                { label: tc("all"), value: "all" },
                { label: t("available"), value: "available" },
                { label: t("allocated"), value: "allocated" },
                { label: t("shipped"), value: "shipped" },
                { label: t("defective"), value: "defective" },
              ]}
            />
          </Form.Item>
        </BaseFilter>
      </Card>

      <Table
        columns={columns}
        dataSource={data?.data?.records || []}
        rowKey="rubber_block_id"
        loading={isFetching}
        pagination={{
          current: data?.data?.current_page,
          pageSize: params.limit,
          total: data?.data?.total_records,
          showSizeChanger: true,
          onChange: (page, limit) => {
            setParams((prev) => ({
              ...prev,
              page,
              limit,
            }));
          },
        }}
        locale={{
          emptyText: <Empty description={t("empty_summary")} />,
        }}
      />
    </Space>
  );
};

export default FgReceiptSummary;
