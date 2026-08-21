"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Modal, Space, message } from "antd";
import React, { useState } from "react";
import { PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import {
  cancelIssue,
  confirmIssue,
  createIssue,
  deleteIssue,
  getIssueByCode,
  getIssues,
  updateIssue,
} from "../actions";
import IssueFilter from "./issue-filter";
import IssueForm from "./issue-form";
import IssueTable from "./issue-table";
import { handleApiError } from "@/lib/api-error";
import { IGetIssuesParams } from "../actions";
import { IIssue, IIssueData } from "../types";
import dayjs from "dayjs";
import IssueDetail from "./issue-detail";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const IssueManager = () => {
  const t = useTranslations("Issue");
  const tc = useTranslations("Common");
  const [params, setParams] = useState<IGetIssuesParams>({
    page: 1,
    limit: 10,
    status: "draft",
  });
  const { trader } = usePermissions();
  const [open, setOpen] = useState(false);
  const [record, setRecord] = useState<any | null>(null);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["issues", params],
    queryFn: () => getIssues(params),
  });

  const createMutation = useMutation({
    mutationFn: createIssue,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["issues"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: any) => updateIssue(record.issue_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["issues"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteIssue,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["issues"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const cancelMutation = useMutation({
    mutationFn: cancelIssue,
    onSuccess: () => {
      message.success(t("cancel_success"));
      queryClient.invalidateQueries({ queryKey: ["issues"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const confirmMutation = useMutation({
    mutationFn: confirmIssue,
    onSuccess: () => {
      message.success(t("confirm_success"));
      queryClient.invalidateQueries({ queryKey: ["issues"] });
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
      search: "",
      sale_order_id: "",
    });
  };

  const handlePageChange = (page: number, limit: number) => {
    setParams({
      ...params,
      page,
      limit,
    });
  };

  const handleEdit = (record: IIssue) => {
    setRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: IIssue) => {
    deleteMutation.mutate(record.issue_code);
  };

  const handleCancel = (record: IIssue) => {
    cancelMutation.mutate(record.issue_code);
  };

  const handleClose = () => {
    setOpen(false);
    setRecord(null);
  };

  const onFinish = async (values: IIssueData) => {
    if (record) {
      updateMutation.mutate({
        ...values,
        issue_date: dayjs(values.issue_date).format("YYYY-MM-DD"),
      });
    } else {
      createMutation.mutate({
        ...values,
        issue_date: dayjs(values.issue_date).format("YYYY-MM-DD"),
      });
    }
  };

  const handleConfirm = (record: IIssue) => {
    confirmMutation.mutate(record.issue_code);
  };

  const handleView = (record: IIssue) => {
    setRecord(record);
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex justify="space-between" align="center">
        <IssueFilter
          onSearch={handleSearch}
          onReset={handleReset}
          loading={isLoading}
        />
        {(trader.issue.full || trader.issue.create) && (
          <TooltipButton
            tooltip={t("create_title")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => setOpen(true)}>
            {tc("add")}
          </TooltipButton>
        )}
      </Flex>

      <IssueTable
        data={Array.isArray(data?.data?.records) ? data.data.records : []}
        loading={isLoading}
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onPageChange={handlePageChange}
        onView={handleView}
        onEdit={handleEdit}
        onDelete={handleDelete}
        onCancel={handleCancel}
        onConfirm={handleConfirm}
        deletingCode={
          deleteMutation.status === "pending"
            ? (deleteMutation.variables as string)
            : null
        }
        processingCode={
          cancelMutation.status === "pending"
            ? (cancelMutation.variables as string)
            : null
        }
        permissions={trader.issue}
      />

      <IssueDetail
        code={record?.issue_code && !open ? record.issue_code : null}
        onClose={handleClose}
      />

      <IssueForm
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

export default IssueManager;
