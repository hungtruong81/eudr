"use client";
import React, { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Form, message, Space, Tag } from "antd";
import { DeleteOutlined, EditOutlined, PlusOutlined } from "@ant-design/icons";
import {
  getProductTypes,
  deleteProductType,
  IGetProductTypeParams,
} from "../action";
import { IProductType } from "../types";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { ProductTypeFilter } from "./product-type-filter";
import { ProductTypeSheet } from "./product-type-sheet";
import { handleApiError } from "@/lib/api-error";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const ProductType = () => {
  const t = useTranslations("Factory.metadata.product_type");
  const tc = useTranslations("Common");

  const [params, setParams] = useState<IGetProductTypeParams>({
    page: 1,
    limit: 10,
  });
  const { productType } = usePermissions();
  const [open, setOpen] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<IProductType | null>(
    null,
  );
  const [filterForm] = Form.useForm();
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["product-types", params],
    queryFn: () => getProductTypes(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteProductType,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["product-types"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleEdit = (record: IProductType) => {
    setSelectedRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: IProductType) => {
    deleteMutation.mutate(record.product_type_code);
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

  const columns: CustomColumnTypeTable<IProductType>[] = [
    {
      title: t("code"),
      dataIndex: "product_type_code",
    },
    {
      title: t("name"),
      dataIndex: "product_type_name",
    },
    {
      title: t("category"),
      dataIndex: "product_type_category",
      render: (category) => {
        const categories: Record<string, string> = {
          scrap_rubber: t("scrap_rubber"),
          concentrated_latex: t("concentrated_latex"),
        };
        const colors: Record<string, string> = {
          scrap_rubber: "orange",
          concentrated_latex: "cyan",
        };
        return (
          <Tag color={colors[category] || "blue"}>
            {categories[category] || category}
          </Tag>
        );
      },
    },
    {
      title: t("weight"),
      dataIndex: "product_weight",
    },
    {
      title: t("description"),
      dataIndex: "description",
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {(productType.full || productType.update) && (
            <TooltipButton
              tooltip={t("edit_tooltip")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
            />
          )}
          {(productType.full || productType.delete) && (
            <ConfirmTooltipButton
              confirmTitle={t("confirm_delete_title")}
              confirmDescription={t("confirm_delete_desc", {
                name: record.product_type_name,
              })}
              tooltip={t("delete_tooltip")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => handleDelete(record)}
              loading={
                deleteMutation.isPending &&
                deleteMutation.variables === record.product_type_code
              }
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="middle">
      <Flex justify="space-between" align="start">
        <ProductTypeFilter
          filterForm={filterForm}
          handleFilter={handleFilter}
          handleResetFilter={handleResetFilter}
        />
        {(productType.full || productType.create) && (
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

      <CustomTable<IProductType>
        rowKey="product_type_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="product-type-table"
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

      <ProductTypeSheet
        open={open}
        onClose={() => setOpen(false)}
        record={selectedRecord}
      />
    </Space>
  );
};

export default ProductType;
