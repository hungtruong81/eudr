"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Modal, Space, message } from "antd";
import React, { useState } from "react";
import { PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import {
  createCustomer,
  deleteCustomer,
  getCustomers,
  updateCustomer,
} from "../actions";
import { ICustomer, ICustomerData } from "../types";
import CustomerFilter from "./customer-filter";
import CustomerForm from "./customer-form";
import CustomerTable from "./customer-table";
import { handleApiError } from "@/lib/api-error";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const CustomerManager = () => {
  const t = useTranslations("Customer");
  const tc = useTranslations("Common");
  const [params, setParams] = useState<any>({
    page: 1,
    limit: 10,
  });
  const { trader } = usePermissions();
  const [open, setOpen] = useState(false);
  const [record, setRecord] = useState<ICustomer | null>(null);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["customers", params],
    queryFn: () => getCustomers(params),
  });

  const createMutation = useMutation({
    mutationFn: createCustomer,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["customers"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({
      customer_code,
      data,
    }: {
      customer_code: string;
      data: ICustomerData;
    }) => updateCustomer(customer_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["customers"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteCustomer,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["customers"] });
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
    });
  };

  const handlePageChange = (page: number, limit: number) => {
    setParams({
      ...params,
      page,
      limit,
    });
  };

  const handleEdit = (record: ICustomer) => {
    setRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: ICustomer) => {
    deleteMutation.mutate(record.customer_code);
  };

  const handleClose = () => {
    setOpen(false);
    setRecord(null);
  };

  const onFinish = async (values: ICustomerData) => {
    if (record) {
      updateMutation.mutate({
        customer_code: record.customer_code,
        data: values,
      });
    } else {
      createMutation.mutate(values);
    }
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex justify="space-between" align="center">
        <CustomerFilter
          onSearch={handleSearch}
          onReset={handleReset}
          loading={isLoading}
        />
        {(trader.customer.full || trader.customer.create) && (
          <TooltipButton
            tooltip={t("create_title")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => setOpen(true)}>
            {tc("add")}
          </TooltipButton>
        )}
      </Flex>

      <CustomerTable
        data={data?.data?.records || []}
        loading={isLoading}
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onPageChange={handlePageChange}
        onEdit={handleEdit}
        onDelete={handleDelete}
        deletingRecord={
          deleteMutation.status === "pending"
            ? (deleteMutation.variables as string)
            : null
        }
        permissions={trader.customer}
      />

      <CustomerForm
        open={open}
        onClose={handleClose}
        record={record}
        onFinish={onFinish}
        loading={
          createMutation.status === "pending" ||
          updateMutation.status === "pending"
        }
      />
    </Space>
  );
};

export default CustomerManager;
