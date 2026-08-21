"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Modal, Space, message } from "antd";
import React, { useState } from "react";
import { PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import { handleApiError } from "@/lib/api-error";
import {
  assignMembers,
  createCompanyGroup,
  deleteCompanyGroup,
  getCompanyGroup,
  getCompanyGroupDetail,
  setGroupPermissions,
  updateCompanyGroup,
  IGetCompanyGroupParams,
} from "../actions";
import {
  ICompanyGroup,
  ICompanyGroupByCode,
  ICompanyGroupData,
} from "../types";
import GroupFilter from "./group-filter";
import GroupTable from "./group-table";
import GroupForm from "./group-form";
import GroupPermissionModal from "./group-permission-modal";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const CompanyGroupManager = () => {
  const [params, setParams] = useState<IGetCompanyGroupParams>({
    page: 1,
    limit: 10,
    status: "all",
    company_id: 0,
  });
  const t = useTranslations("Manage.GroupPermission");
  const tc = useTranslations("Common");
  const { companyGroup } = usePermissions();
  const [open, setOpen] = useState(false);
  const [permissionOpen, setPermissionOpen] = useState(false);
  const [memberOpen, setMemberOpen] = useState(false);
  const [record, setRecord] = useState<ICompanyGroup | null>(null);
  const [detailRecord, setDetailRecord] = useState<ICompanyGroupByCode | null>(
    null,
  );
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["company-groups", params],
    queryFn: () => getCompanyGroup(params),
    enabled: !!params.company_id || params.status === "all",
  });

  const createMutation = useMutation({
    mutationFn: (data: ICompanyGroupData) => createCompanyGroup({ ...data }),
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["company-groups"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: ICompanyGroupData) =>
      updateCompanyGroup(record!.company_group_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["company-groups"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteCompanyGroup,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["company-groups"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const permissionMutation = useMutation({
    mutationFn: (permissions: string[]) =>
      setGroupPermissions(detailRecord!.company_group_code, { permissions }),
    onSuccess: () => {
      message.success(t("update_permissions_success"));
      setPermissionOpen(false);
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const assignMemberMutation = useMutation({
    mutationFn: (data: { assign: number[]; remove: number[] }) =>
      assignMembers(record!.company_group_code, {
        assign_user_ids: data.assign,
        remove_user_ids: data.remove,
      }),
    onSuccess: () => {
      message.success(t("update_members_success"));
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
      company_id: 0,
    });
  };

  const handlePageChange = (page: number, limit: number) => {
    setParams({
      ...params,
      page,
      limit,
    });
  };

  const handleEdit = (record: ICompanyGroup) => {
    setRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: ICompanyGroup) => {
    deleteMutation.mutate(record.company_group_code);
  };

  const handleAssignPermissions = async (record: ICompanyGroup) => {
    try {
      const res = await getCompanyGroupDetail(record.company_group_code);
      setDetailRecord(res.data);
      setPermissionOpen(true);
    } catch (error) {
      handleApiError(error);
    }
  };

  const handleAssignMembers = (record: ICompanyGroup) => {
    setRecord(record);
    setMemberOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
    setPermissionOpen(false);
    setMemberOpen(false);
    setRecord(null);
    setDetailRecord(null);
  };

  const onFinish = async (values: ICompanyGroupData) => {
    if (record) {
      updateMutation.mutate(values);
    } else {
      createMutation.mutate(values);
    }
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex justify="space-between" align="center">
        <GroupFilter
          onSearch={handleSearch}
          onReset={handleReset}
          loading={isLoading}
        />
        {(companyGroup.full || companyGroup.create) && (
          <TooltipButton
            tooltip={t("create_title")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => setOpen(true)}>
            {tc("add_new")}
          </TooltipButton>
        )}
      </Flex>

      <GroupTable
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
        onAssignMembers={handleAssignMembers}
        onAssignPermissions={handleAssignPermissions}
        deletingCode={
          deleteMutation.status === "pending"
            ? (deleteMutation.variables as string)
            : null
        }
        permissions={companyGroup}
      />

      <GroupForm
        open={open}
        onClose={handleClose}
        record={record}
        onFinish={onFinish}
        loading={
          createMutation.status === "pending" ||
          updateMutation.status === "pending"
        }
      />

      <GroupPermissionModal
        open={permissionOpen}
        onClose={() => setPermissionOpen(false)}
        record={detailRecord}
        onFinish={(perms: string[]) => permissionMutation.mutateAsync(perms)}
        loading={permissionMutation.status === "pending"}
      />
    </Space>
  );
};

export default CompanyGroupManager;
