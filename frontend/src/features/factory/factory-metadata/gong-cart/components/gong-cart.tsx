"use client";
import React, { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Form, message, Space, Tag } from "antd";
import { DeleteOutlined, EditOutlined, PlusOutlined } from "@ant-design/icons";
import { getProductionGongCarts, deleteProductionGongCart } from "../actions";
import { IProductionGongCart } from "../types";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { GongCartFilter } from "./gong-cart-filter";
import { GongCartSheet } from "./gong-cart-sheet";
import { handleApiError } from "@/lib/api-error";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

export const GongCart = () => {
  const t = useTranslations("Factory.metadata.gong_cart");
  const tc = useTranslations("Common");

  const [params, setParams] = useState<any>({
    page: 1,
    limit: 10,
  });
  const { factory } = usePermissions();
  const [open, setOpen] = useState(false);
  const [selectedRecord, setSelectedRecord] =
    useState<IProductionGongCart | null>(null);
  const [filterForm] = Form.useForm();
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["gong-carts", params],
    queryFn: () => getProductionGongCarts(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteProductionGongCart,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["gong-carts"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleEdit = (record: IProductionGongCart) => {
    setSelectedRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: IProductionGongCart) => {
    deleteMutation.mutate(record.gong_cart_code);
  };

  const handleFilter = (values: any) => {
    setParams({
      ...params,
      ...values,
      page: 1,
    });
  };

  const handleResetFilter = () => {
    setParams({
      page: 1,
      limit: 10,
    });
  };

  const columns: CustomColumnTypeTable<IProductionGongCart>[] = [
    {
      title: t("name"),
      dataIndex: "gong_cart_name",
    },
    {
      title: t("code"),
      dataIndex: "gong_cart_code",
      render: (text) => text?.toUpperCase(),
    },
    {
      title: t("factory"),
      dataIndex: "factory_name",
    },
    {
      title: t("max_poles"),
      dataIndex: "max_poles",
      type: "number",
    },
    {
      title: t("status"),
      dataIndex: "status",
      render: (status: string) => {
        let color = "default";
        let label = status;
        if (status === "available") {
          color = "success";
          label = t("status_available");
        } else if (status === "in_use") {
          color = "processing";
          label = t("status_in_use");
        } else if (status === "cleaning") {
          color = "cyan";
          label = t("status_cleaning");
        }
        return <Tag color={color}>{label}</Tag>;
      },
    },
    {
      title: tc("created_at"),
      dataIndex: "created_at",
      type: "date",
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {(factory.full || factory.update) && (
            <TooltipButton
              tooltip={t("edit_tooltip")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
            />
          )}
          {(factory.full || factory.delete) && (
            <ConfirmTooltipButton
              confirmTitle={t("confirm_delete_title")}
              confirmDescription={t("confirm_delete_desc", {
                name: record.gong_cart_name,
              })}
              tooltip={t("delete_tooltip")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => handleDelete(record)}
              loading={
                deleteMutation.isPending &&
                deleteMutation.variables === record.gong_cart_code
              }
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <Space direction="vertical" style={{ width: "100%" }} size="middle">
      <Flex justify="space-between" align="start">
        <GongCartFilter
          filterForm={filterForm}
          handleFilter={handleFilter}
          handleResetFilter={handleResetFilter}
        />
        {(factory.full || factory.create) && (
          <TooltipButton
            tooltip={t("add_tooltip")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => {
              setSelectedRecord(null);
              setOpen(true);
            }}>
            {tc("add")}
          </TooltipButton>
        )}
      </Flex>

      <CustomTable<IProductionGongCart>
        rowKey="gong_cart_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="gong-cart-table"
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
        scroll={{ x: "max-content" }}
      />

      <GongCartSheet
        open={open}
        onClose={() => setOpen(false)}
        record={selectedRecord}
      />
    </Space>
  );
};
