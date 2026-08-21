"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Space, message } from "antd";
import React, { useState } from "react";
import { useTranslations } from "next-intl";

import {
  createPallet,
  addPalletItem,
  getPallet,
  cancelPallet,
  deletePallet,
} from "../actions";
import { getFactory } from "@/features/factory/factory-metadata/factory/actions";
import { IPallet, IPalletData } from "../types";
import { handleApiError } from "@/lib/api-error";
import { TooltipButton } from "@/components/tooltip-button";
import { PlusOutlined } from "@ant-design/icons";

import PalletFilter from "./pallet-filter";
import PalletTable from "./pallet-table";
import PalletForm from "./pallet-form";
import PalletDetailModal from "./pallet-detail-modal";
import { usePermissions } from "@/contexts/permission-context";

const PalletManager = () => {
  const t = useTranslations("Pallet");
  const tc = useTranslations("Common");
  const queryClient = useQueryClient();
  const { trader } = usePermissions();

  const [params, setParams] = useState({
    page: 1,
    limit: 10,
    search: "",
    status: undefined as string | undefined,
  });

  const [openForm, setOpenForm] = useState(false);
  const [openDetail, setOpenDetail] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<IPallet | null>(null);
  const [deletingCode, setDeletingCode] = useState<string | null>(null);
  const [processingCode, setProcessingCode] = useState<string | null>(null);

  // Fetch Pallets
  const { data, isLoading } = useQuery({
    queryKey: ["pallet", params],
    queryFn: () => getPallet(params),
  });

  // Fetch Factories/Warehouses list for mapping names
  const { data: factoriesData } = useQuery({
    queryKey: ["factories-all-pallet"],
    queryFn: () => getFactory({ page: 1, limit: 100 }),
  });

  const factories = factoriesData?.data?.records || [];

  // Create Pallet Mutation
  const createMutation = useMutation({
    mutationFn: async (payload: IPalletData) => {
      // Step 1: Create the pallet record
      const res = await createPallet({
        pallet_code: payload.pallet_code,
        warehouse_id: payload.warehouse_id,
        rubber_block_ids: [],
      });

      // Step 2: If rubber blocks were selected, associate them
      if (payload.rubber_block_ids && payload.rubber_block_ids.length > 0) {
        await addPalletItem(payload.pallet_code, {
          rubber_block_ids: payload.rubber_block_ids.map(String),
        });
      }

      return res;
    },
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["pallet"] });
      queryClient.invalidateQueries({ queryKey: ["available-rubber-blocks"] });
      handleCloseForm();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  // Edit Pallet Mutation (Adding selected rubber blocks to existing Pallet)
  const editMutation = useMutation({
    mutationFn: async (payload: IPalletData) => {
      if (payload.rubber_block_ids && payload.rubber_block_ids.length > 0) {
        return await addPalletItem(payload.pallet_code, {
          rubber_block_ids: payload.rubber_block_ids.map(String),
        });
      }
      return null;
    },
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["pallet"] });
      queryClient.invalidateQueries({ queryKey: ["available-rubber-blocks"] });
      handleCloseForm();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  // Cancel Pallet Mutation
  const cancelMutation = useMutation({
    mutationFn: cancelPallet,
    onMutate: (code) => {
      setProcessingCode(code);
    },
    onSuccess: () => {
      message.success(t("cancel_success"));
      queryClient.invalidateQueries({ queryKey: ["pallet"] });
      queryClient.invalidateQueries({ queryKey: ["available-rubber-blocks"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
    onSettled: () => {
      setProcessingCode(null);
    },
  });

  // Delete Pallet Mutation
  const deleteMutation = useMutation({
    mutationFn: deletePallet,
    onMutate: (code) => {
      setDeletingCode(code);
    },
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["pallet"] });
      queryClient.invalidateQueries({ queryKey: ["available-rubber-blocks"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
    onSettled: () => {
      setDeletingCode(null);
    },
  });

  const handleOpenForm = (record: IPallet | null = null) => {
    setSelectedRecord(record);
    setOpenForm(true);
  };

  const handleCloseForm = () => {
    setOpenForm(false);
    setSelectedRecord(null);
  };

  const handleOpenDetail = (record: IPallet) => {
    setSelectedRecord(record);
    setOpenDetail(true);
  };

  const handleCloseDetail = () => {
    setOpenDetail(false);
    setSelectedRecord(null);
  };

  const handleFinish = async (values: IPalletData) => {
    if (selectedRecord) {
      await editMutation.mutateAsync(values);
    } else {
      await createMutation.mutateAsync(values);
    }
  };

  const handleSearch = (filterValues: any) => {
    setParams((prev) => ({
      ...prev,
      page: 1,
      search: filterValues.search || "",
      status: filterValues.status || undefined,
    }));
  };

  const handleReset = () => {
    setParams({
      page: 1,
      limit: 10,
      search: "",
      status: undefined,
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
      <PalletFilter
        onSearch={handleSearch}
        onReset={handleReset}
        loading={isLoading}
      />

      {(trader.pallet.create || trader.pallet.full) && (
        <Flex justify="end">
          <TooltipButton
            tooltip={t("create_title")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => handleOpenForm(null)}>
            {tc("add")}
          </TooltipButton>
        </Flex>
      )}

      <PalletTable
        data={data?.data?.records}
        loading={isLoading}
        factories={factories}
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onPageChange={handlePageChange}
        onEdit={handleOpenForm}
        onDelete={(rec) => deleteMutation.mutate(rec.pallet_code)}
        onCancel={(rec) => cancelMutation.mutate(rec.pallet_code)}
        onView={handleOpenDetail}
        deletingCode={deletingCode}
        processingCode={processingCode}
      />

      <PalletForm
        open={openForm}
        onClose={handleCloseForm}
        record={selectedRecord}
        onFinish={handleFinish}
        loading={createMutation.isPending || editMutation.isPending}
      />

      <PalletDetailModal
        open={openDetail}
        onClose={handleCloseDetail}
        pallet={selectedRecord}
        factories={factories}
      />
    </Space>
  );
};

export default PalletManager;
