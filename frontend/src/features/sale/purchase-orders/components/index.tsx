"use client";
import { useQuery } from "@tanstack/react-query";
import { Flex, Space } from "antd";
import React, { useState } from "react";
import { getPurchaseOrders, IGetPurchaseOrderParams } from "../actions";
import PurchaseOrderFilter from "./purchase-order-filter";
import PurchaseOrderTable from "./purchase-order-table";
import { usePermissions } from "@/contexts/permission-context";

const PurchaseOrders = () => {
  const [params, setParams] = useState<Partial<IGetPurchaseOrderParams>>({
    page: 1,
    limit: 10,
    status: "all",
  });
  const { trader } = usePermissions();

  const { data, isLoading } = useQuery({
    queryKey: ["purchase-orders", params],
    queryFn: () => getPurchaseOrders(params),
  });

  const handleSearch = (values: any) => {
    setParams({
      ...params,
      ...values,
      page: 1,
    });
  };

  const handleReset = () => {
    setParams({
      page: 1,
      limit: 10,
      status: "all",
    });
  };

  const handlePageChange = (page: number, limit: number) => {
    setParams({
      ...params,
      page,
      limit,
    });
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex justify="space-between" align="center">
        <PurchaseOrderFilter
          onSearch={handleSearch}
          onReset={handleReset}
          loading={isLoading}
        />
      </Flex>

      <PurchaseOrderTable
        data={Array.isArray(data?.data?.records) ? data.data.records : []}
        loading={isLoading}
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onPageChange={handlePageChange}
        permissions={trader.order}
      />
    </Space>
  );
};

export default PurchaseOrders;
