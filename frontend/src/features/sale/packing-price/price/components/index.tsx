"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Space, message } from "antd";
import React, { useState } from "react";
import { useTranslations } from "next-intl";

import {
  createPrice,
  getPrices,
  updatePrice,
  deletePrice,
} from "../actions";
import { IPrice, IPriceData } from "../types";
import { handleApiError } from "@/lib/api-error";
import { TooltipButton } from "@/components/tooltip-button";
import { PlusOutlined } from "@ant-design/icons";

import PriceFilter from "./price-filter";
import PriceTable from "./price-table";
import PriceForm from "./price-form";
import { usePermissions } from "@/contexts/permission-context";

const PriceManager = () => {
  const t = useTranslations("Pallet");
  const tc = useTranslations("Common");
  const queryClient = useQueryClient();
  const { trader } = usePermissions();

  const [params, setParams] = useState({
    page: 1,
    limit: 10,
    search: "",
    price_type: undefined as string | undefined,
  });

  const [openForm, setOpenForm] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<IPrice | null>(null);
  const [deletingCode, setDeletingCode] = useState<string | null>(null);

  // Fetch Prices
  const { data, isLoading } = useQuery({
    queryKey: ["prices", params],
    queryFn: () => getPrices(params),
  });

  // Create Price Mutation
  const createMutation = useMutation({
    mutationFn: createPrice,
    onSuccess: () => {
      message.success(t("create_price_success"));
      queryClient.invalidateQueries({ queryKey: ["prices"] });
      handleCloseForm();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  // Edit Price Mutation
  const editMutation = useMutation({
    mutationFn: async ({
      price_code,
      payload,
    }: {
      price_code: string;
      payload: IPriceData;
    }) => {
      return await updatePrice(price_code, payload);
    },
    onSuccess: () => {
      message.success(t("update_price_success"));
      queryClient.invalidateQueries({ queryKey: ["prices"] });
      handleCloseForm();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  // Delete Price Mutation
  const deleteMutation = useMutation({
    mutationFn: deletePrice,
    onMutate: (code) => {
      setDeletingCode(code);
    },
    onSuccess: () => {
      message.success(t("delete_price_success"));
      queryClient.invalidateQueries({ queryKey: ["prices"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
    onSettled: () => {
      setDeletingCode(null);
    },
  });

  const handleOpenForm = (record: IPrice | null = null) => {
    setSelectedRecord(record);
    setOpenForm(true);
  };

  const handleCloseForm = () => {
    setOpenForm(false);
    setSelectedRecord(null);
  };

  const handleFinish = async (values: IPriceData) => {
    if (selectedRecord) {
      await editMutation.mutateAsync({
        price_code: selectedRecord.price_code,
        payload: values,
      });
    } else {
      await createMutation.mutateAsync(values);
    }
  };

  const handleSearch = (filterValues: any) => {
    setParams((prev) => ({
      ...prev,
      page: 1,
      search: filterValues.search || "",
      price_type: filterValues.price_type || undefined,
    }));
  };

  const handleReset = () => {
    setParams({
      page: 1,
      limit: 10,
      search: "",
      price_type: undefined,
    });
  };

  const handlePageChange = (page: number, pageSize: number) => {
    setParams((prev) => ({
      ...prev,
      page,
      limit: pageSize,
    }));
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="middle">
      <PriceFilter
        onSearch={handleSearch}
        onReset={handleReset}
        loading={isLoading}
      />

      {(trader.price.create || trader.price.full) && (
        <Flex justify="end">
          <TooltipButton
            tooltip={t("create_price_title")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => handleOpenForm(null)}>
            {tc("add")}
          </TooltipButton>
        </Flex>
      )}

      <PriceTable
        data={data?.data?.records}
        loading={isLoading}
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onPageChange={handlePageChange}
        onEdit={handleOpenForm}
        onDelete={(rec) => deleteMutation.mutate(rec.price_code)}
        deletingCode={deletingCode}
      />

      <PriceForm
        open={openForm}
        onClose={handleCloseForm}
        record={selectedRecord}
        onFinish={handleFinish}
        loading={createMutation.isPending || editMutation.isPending}
      />
    </Space>
  );
};

export default PriceManager;
