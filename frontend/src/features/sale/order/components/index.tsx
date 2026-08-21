"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Modal, Space, message } from "antd";
import React, { useState } from "react";
import { PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import {
  approveOrder,
  cancelOrder,
  createOrder,
  deleteOrder,
  getOrders,
  IGetOrderParams,
  updateOrder,
} from "../actions";
import { IOrder, IOrderData } from "../types";
import OrderFilter from "./order-filter";
import OrderForm from "./order-form";
import OrderTable from "./order-table";
import OrderDetail from "./order-detail";
import { handleApiError } from "@/lib/api-error";
import dayjs from "dayjs";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const OrderManager = () => {
  const [params, setParams] = useState<Partial<IGetOrderParams>>({
    page: 1,
    limit: 10,
    status: "all",
  });
  const ts = useTranslations("Sales");
  const tc = useTranslations("Common");
  const { trader } = usePermissions();
  const [open, setOpen] = useState(false);
  const [viewOpen, setViewOpen] = useState(false);
  const [record, setRecord] = useState<IOrder | null>(null);
  const [viewRecord, setViewRecord] = useState<IOrder | null>(null);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["orders", params],
    queryFn: () => getOrders(params),
  });

  const createMutation = useMutation({
    mutationFn: createOrder,
    onSuccess: () => {
      message.success(ts("create_success"));
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: updateOrder,
    onSuccess: () => {
      message.success(ts("update_success"));
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteOrder,
    onSuccess: () => {
      message.success(ts("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["orders"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const approveMutation = useMutation({
    mutationFn: approveOrder,
    onSuccess: () => {
      message.success(ts("approve_success"));
      queryClient.invalidateQueries({ queryKey: ["orders"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const cancelMutation = useMutation({
    mutationFn: cancelOrder,
    onSuccess: () => {
      message.success(ts("cancel_success"));
      queryClient.invalidateQueries({ queryKey: ["orders"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
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
      order_source_type: "warehouse",
    });
  };

  const handlePageChange = (page: number, limit: number) => {
    setParams({
      ...params,
      page,
      limit,
    });
  };

  const handleView = (record: IOrder) => {
    setViewRecord(record);
    setViewOpen(true);
  };

  const handleEdit = (record: IOrder) => {
    setRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: IOrder) => {
    deleteMutation.mutate(record.sale_order_code);
  };

  const handleApprove = (record: IOrder) => {
    approveMutation.mutate(record.sale_order_code);
  };

  const handleCancel = (record: IOrder) => {
    cancelMutation.mutate(record.sale_order_code);
  };

  const handleClose = () => {
    setOpen(false);
    setRecord(null);
  };

  const handleViewClose = () => {
    setViewOpen(false);
    setViewRecord(null);
  };

  const onFinish = async (values: IOrderData) => {
    if (record) {
      updateMutation.mutate({
        ...values,
        delivery_date: dayjs(values.delivery_date).format("YYYY-MM-DD"),
      });
    } else {
      createMutation.mutate({
        ...values,
        delivery_date: dayjs(values.delivery_date).format("YYYY-MM-DD"),
      });
    }
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex justify="space-between" align="center">
        <OrderFilter
          onSearch={handleSearch}
          onReset={handleReset}
          loading={isLoading}
        />
        {(trader.order.full || trader.order.create) && (
          <TooltipButton
            tooltip={ts("add_order")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => setOpen(true)}>
            {tc("add")}
          </TooltipButton>
        )}
      </Flex>

      <OrderTable
        data={Array.isArray(data?.data?.records) ? data.data.records : []}
        loading={isLoading}
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onPageChange={handlePageChange}
        onEdit={handleEdit}
        onDelete={handleDelete}
        onApprove={handleApprove}
        onCancel={handleCancel}
        onView={handleView}
        deletingCode={
          deleteMutation.status === "pending"
            ? (deleteMutation.variables as string)
            : null
        }
        processingCode={
          approveMutation.status === "pending"
            ? (approveMutation.variables as string)
            : cancelMutation.status === "pending"
              ? (cancelMutation.variables as string)
              : null
        }
        permissions={trader.order}
      />

      <OrderForm
        open={open}
        onClose={handleClose}
        record={record}
        onFinish={onFinish}
        loading={
          createMutation.status === "pending" ||
          updateMutation.status === "pending"
        }
      />

      <OrderDetail
        open={viewOpen}
        onClose={handleViewClose}
        record={viewRecord}
      />
    </Space>
  );
};

export default OrderManager;
