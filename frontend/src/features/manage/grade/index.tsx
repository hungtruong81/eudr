"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Space, message } from "antd";
import React, { useState } from "react";
import { PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import { handleApiError } from "@/lib/api-error";
import {
  createGrade,
  deleteGrade,
  getGrade,
  updateGrade,
  createGradePrice,
  generateCodeGrade,
  IGetGradeParams,
} from "./actions";
import { IGrade, IGradeData, IGradePriceData } from "./type";
import GradeFilter from "./components/grade-filter";
import GradeTable from "./components/grade-table";
import GradeForm from "./components/grade-form";
import GradePriceForm from "./components/grade-price-form";
import GradePriceHistorySheet from "./components/grade-price-history-sheet";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const GradeManager = () => {
  const [params, setParams] = useState<IGetGradeParams>({
    page: 1,
    limit: 10,
    search: undefined,
  });
  const t = useTranslations("Manage.Grade");
  const tc = useTranslations("Common");
  const { company } = usePermissions();
  const [open, setOpen] = useState(false);
  const [openPrice, setOpenPrice] = useState(false);
  const [historyOpen, setHistoryOpen] = useState(false);
  const [record, setRecord] = useState<IGrade | null>(null);
  const [historyRecord, setHistoryRecord] = useState<IGrade | null>(null);
  const [loadingCode, setLoadingCode] = useState(false);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["grades", params],
    queryFn: () => getGrade(params),
  });

  const createMutation = useMutation({
    mutationFn: createGrade,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["grades"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: IGradeData) => updateGrade(record!.grade_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["grades"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteGrade,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["grades"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updatePriceMutation = useMutation({
    mutationFn: ({
      grade_code,
      data,
    }: {
      grade_code: string;
      data: IGradePriceData;
    }) => createGradePrice(grade_code, data),
    onSuccess: (_, variables) => {
      message.success(t("update_price_success"));
      queryClient.invalidateQueries({ queryKey: ["grades"] });
      queryClient.invalidateQueries({
        queryKey: ["grade-price-current", variables.grade_code],
      });
      queryClient.invalidateQueries({
        queryKey: ["grade-price-history", variables.grade_code],
      });
      handleClosePrice();
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
      search: undefined,
    });
  };

  const handlePageChange = (page: number, limit: number) => {
    setParams({
      ...params,
      page,
      limit,
    });
  };

  const handleCreate = async () => {
    setLoadingCode(true);
    try {
      const res = await generateCodeGrade();
      const newGradeCode = res.data?.grade_code;
      if (newGradeCode) {
        setRecord({ grade_code: newGradeCode } as IGrade);
        setOpen(true);
      }
    } catch (error) {
      handleApiError(error);
    } finally {
      setLoadingCode(false);
    }
  };

  const handleEdit = (record: IGrade) => {
    setRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: IGrade) => {
    deleteMutation.mutate(record.grade_code);
  };

  const handleUpdatePrice = (record: IGrade) => {
    setRecord(record);
    setOpenPrice(true);
  };

  const handleOpenHistory = (record: IGrade) => {
    setHistoryRecord(record);
    setHistoryOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
    setRecord(null);
  };

  const handleClosePrice = () => {
    setOpenPrice(false);
    setRecord(null);
  };

  const handleCloseHistory = () => {
    setHistoryOpen(false);
    setHistoryRecord(null);
  };

  const onFinish = async (values: IGradeData) => {
    if (record && record.grade_id) {
      updateMutation.mutate({ ...values, grade_code: record.grade_code });
    } else {
      createMutation.mutate({ ...values, grade_code: record!.grade_code });
    }
  };

  const onFinishPrice = async (values: IGradePriceData) => {
    if (!record) return;
    updatePriceMutation.mutate({
      grade_code: record.grade_code,
      data: values,
    });
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex justify="space-between" align="center">
        <GradeFilter
          onSearch={handleSearch}
          onReset={handleReset}
          loading={isLoading}
        />
        {(company.full || company.create) && (
          <TooltipButton
            tooltip={t("create_title")}
            type="primary"
            icon={<PlusOutlined />}
            loading={loadingCode}
            onClick={handleCreate}>
            {tc("add_new")}
          </TooltipButton>
        )}
      </Flex>

      <GradeTable
        data={data?.data?.records}
        loading={isLoading}
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onPageChange={handlePageChange}
        onEdit={handleEdit}
        onDelete={handleDelete}
        onUpdatePrice={handleUpdatePrice}
        onViewHistory={handleOpenHistory}
        deletingCode={
          deleteMutation.status === "pending"
            ? (deleteMutation.variables as string)
            : null
        }
        permissions={company}
      />

      <GradeForm
        open={open}
        onClose={handleClose}
        record={record}
        onFinish={onFinish}
        loading={
          createMutation.status === "pending" ||
          updateMutation.status === "pending"
        }
      />

      <GradePriceForm
        open={openPrice}
        onClose={handleClosePrice}
        record={record}
        onFinish={onFinishPrice}
        loading={updatePriceMutation.status === "pending"}
      />

      <GradePriceHistorySheet
        open={historyOpen}
        onClose={handleCloseHistory}
        record={historyRecord}
      />
    </Space>
  );
};

export default GradeManager;
