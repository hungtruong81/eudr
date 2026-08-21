"use client";
import React, { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Form, message, Space } from "antd";
import {
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import {
  getProductTank,
  deleteProductTank,
  IGetProductTankParams,
} from "../actions";
import { IProductTank } from "../types";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { ProductTankFilter } from "./product-tank-filter";
import { ProductTankSheet } from "./product-tank-sheet";
import ProductTankHistory from "./product-tank-history";
import { handleApiError } from "@/lib/api-error";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const ProductTank = () => {
  const t = useTranslations("Factory.metadata.product_tank");
  const tc = useTranslations("Common");

  const [params, setParams] = useState<IGetProductTankParams>({
    page: 1,
    limit: 10,
  });
  const { productTank } = usePermissions();
  const [open, setOpen] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<IProductTank | null>(
    null,
  );
  const [historyRecord, setHistoryRecord] = useState<IProductTank | null>(null);
  const [filterForm] = Form.useForm();
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["product-tank", params],
    queryFn: () => getProductTank(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteProductTank,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["product-tank"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleEdit = (record: IProductTank) => {
    setSelectedRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: IProductTank) => {
    deleteMutation.mutate(record.product_tank_code);
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

  const columns: CustomColumnTypeTable<IProductTank>[] = [
    {
      title: t("name"),
      dataIndex: "product_tank_name",
    },
    {
      title: t("factory"),
      dataIndex: "factory_name",
    },
    {
      title: t("product_type"),
      dataIndex: "product_type",
    },
    {
      title: t("capacity"),
      dataIndex: "capacity",
      type: "number",
    },
    {
      title: t("current_volume"),
      dataIndex: "current_volume",
      type: "number",
    },
    {
      title: t("location"),
      dataIndex: "location",
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
          {(productTank.full || productTank.update) && (
            <TooltipButton
              tooltip={t("edit_tooltip")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
            />
          )}
          {(productTank.full || productTank.delete) && (
            <ConfirmTooltipButton
              confirmTitle={t("confirm_delete_title")}
              confirmDescription={t("confirm_delete_desc", {
                name: record.product_tank_name,
              })}
              tooltip={t("delete_tooltip")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => handleDelete(record)}
              loading={
                deleteMutation.isPending &&
                deleteMutation.variables === record.product_tank_code
              }
            />
          )}
          <TooltipButton
            tooltip={t("history_tooltip")}
            type="dashed"
            icon={<EyeOutlined />}
            onClick={() => setHistoryRecord(record)}
          />
        </Space>
      ),
    },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex justify="space-between" align="start">
        <ProductTankFilter
          filterForm={filterForm}
          handleFilter={handleFilter}
          handleResetFilter={handleResetFilter}
        />
        {(productTank.full || productTank.create) && (
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

      <CustomTable<IProductTank>
        rowKey="product_tank_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="product-tank-table"
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

      <ProductTankSheet
        open={open}
        onClose={() => setOpen(false)}
        record={selectedRecord}
      />

      <ProductTankHistory
        productTankCode={historyRecord?.product_tank_code || ""}
        open={!!historyRecord}
        onClose={() => setHistoryRecord(null)}
      />
    </Space>
  );
};

export default ProductTank;
