"use client";
import React, { useState } from "react";
import dayjs from "dayjs";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Space, Row, Col, Spin, Empty, Pagination, message } from "antd";
import { PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import {
  getProductionOrders,
  deleteProductionOrder,
  IGetProductOrderParams,
} from "../actions";
import { IProductionOrder } from "../types";
import ProductOrderFilter from "./product-order-filter";
import ProductOrderCard from "./product-order-card";
import ProductOrderForm from "./product-order-form";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

const ProductionOrder = () => {
  const t = useTranslations("Factory.product_order");
  const tc = useTranslations("Common");

  const [params, setParams] = useState<Partial<IGetProductOrderParams>>({
    page: 1,
    limit: 12,
    production_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
    production_date_to: dayjs().format("YYYY-MM-DD"),
  });

  const [openForm, setOpenForm] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<IProductionOrder | null>(
    null,
  );

  const { data, isFetching, refetch } = useQuery({
    queryKey: ["production-orders", params],
    queryFn: () => getProductionOrders(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteProductionOrder,
    onSuccess: () => {
      message.success(t("delete_success"));
      refetch();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleSearch = (newParams: Partial<IGetProductOrderParams>) => {
    setParams((prev) => ({
      ...prev,
      ...newParams,
      page: 1,
    }));
  };

  const handleEdit = (order: IProductionOrder) => {
    setSelectedOrder(order);
    setOpenForm(true);
  };

  const handleAdd = () => {
    setSelectedOrder(null);
    setOpenForm(true);
  };

  const handleDelete = (code: string) => {
    deleteMutation.mutate(code);
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex align="center" justify="space-between">
        <ProductOrderFilter onSearch={handleSearch} />

        <TooltipButton
          tooltip={t("add_title")}
          icon={<PlusOutlined />}
          type="primary"
          onClick={handleAdd}>
          {tc("add")}
        </TooltipButton>
      </Flex>

      <Spin spinning={isFetching}>
        {(!data?.data || data.data.records.length === 0) && !isFetching ? (
          <Empty description={t("empty_description")} />
        ) : (
          <Flex vertical gap="large">
            <Row gutter={[16, 16]}>
              {data?.data?.records?.map((order) => (
                <Col
                  xs={24}
                  sm={24}
                  md={12}
                  lg={8}
                  xl={6}
                  key={order.production_order_id}>
                  <ProductOrderCard
                    order={order}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    deleting={
                      deleteMutation.isPending &&
                      deleteMutation.variables === order.production_order_code
                    }
                  />
                </Col>
              ))}
            </Row>

            {data && data.data.total_records > 0 && (
              <Flex justify="flex-end">
                <Pagination
                  current={+data.data.current_page}
                  total={+data.data.total_records}
                  pageSize={+data.data.page_limit}
                  showSizeChanger
                  onChange={(page, limit) => {
                    setParams((prev) => ({
                      ...prev,
                      page,
                      limit,
                    }));
                  }}
                />
              </Flex>
            )}
          </Flex>
        )}
      </Spin>

      <ProductOrderForm
        open={openForm}
        onClose={() => setOpenForm(false)}
        order={selectedOrder}
        onSuccess={refetch}
      />
    </Space>
  );
};

export default ProductionOrder;
