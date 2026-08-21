"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Modal, Space, message } from "antd";
import React, { useState } from "react";
import { PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import { handleApiError } from "@/lib/api-error";
import {
  createCompany,
  deleteCompany,
  getCompanys,
  updateCompany,
  IGetCompanyParams,
} from "../actions";
import { ICompany, ICompanyData } from "../types";
import CompanyFilter from "./company-filter";
import CompanyTable from "./company-table";
import CompanyForm from "./company-form";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const CompanyManager = () => {
  const [params, setParams] = useState<IGetCompanyParams>({
    page: 1,
    limit: 10,
    status: "all",
  });
  const t = useTranslations("Manage.Company");
  const tc = useTranslations("Common");
  const { company } = usePermissions();
  const [open, setOpen] = useState(false);
  const [record, setRecord] = useState<ICompany | null>(null);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["companies", params],
    queryFn: () => getCompanys(params),
  });

  const createMutation = useMutation({
    mutationFn: createCompany,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["companies"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: ICompanyData) =>
      updateCompany(record!.company_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["companies"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteCompany,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["companies"] });
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
    });
  };

  const handlePageChange = (page: number, limit: number) => {
    setParams({
      ...params,
      page,
      limit,
    });
  };

  const handleEdit = (record: ICompany) => {
    setRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: ICompany) => {
    deleteMutation.mutate(record.company_code);
  };

  const handleClose = () => {
    setOpen(false);
    setRecord(null);
  };

  const onFinish = async (values: ICompanyData) => {
    if (record) {
      updateMutation.mutate(values);
    } else {
      createMutation.mutate(values);
    }
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex justify="space-between" align="center">
        <CompanyFilter
          onSearch={handleSearch}
          onReset={handleReset}
          loading={isLoading}
        />
        {(company.full || company.create) && (
          <TooltipButton
            tooltip={t("create_title")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => setOpen(true)}>
            {tc("add_new")}
          </TooltipButton>
        )}
      </Flex>

      <CompanyTable
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
        deletingCode={
          deleteMutation.status === "pending"
            ? (deleteMutation.variables as string)
            : null
        }
        permissions={company}
      />

      <CompanyForm
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

export default CompanyManager;
