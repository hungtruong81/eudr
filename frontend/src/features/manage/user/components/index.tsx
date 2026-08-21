"use client";

import { TooltipButton } from "@/components/tooltip-button";
import { usePermissions } from "@/contexts/permission-context";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";
import { PlusOutlined } from "@ant-design/icons";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Card, Flex, message, Space, Typography } from "antd";
import { useState } from "react";
import {
  createCompanyMember,
  deActiveUser,
  deleteCompanyMember,
  getUserCompany,
  updateCompanyMember,
} from "../actions";
import {
  IUpdateUserCompanyData,
  IUserCompany,
  IUserCompanyData,
} from "../types";
import RoleModal from "./role-modal";
import UserFilter from "./user-filter";
import UserForm from "./user-form";
import UserTable from "./user-table";

const { Title } = Typography;

const UserManager = () => {
  const queryClient = useQueryClient();
  const [params, setParams] = useState<any>({ page: 1, limit: 10 });
  const t = useTranslations("Manage.User");
  const tc = useTranslations("Common");
  const { companyMember } = usePermissions();
  // Form states
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<IUserCompany | null>(null);

  // Role Modal states
  const [isRoleModalOpen, setIsRoleModalOpen] = useState(false);

  // Queries
  const { data, isLoading } = useQuery({
    queryKey: ["company-members", params],
    queryFn: () => getUserCompany(params),
  });

  // Mutations
  const createMutation = useMutation({
    mutationFn: createCompanyMember,
    onSuccess: () => {
      message.success(tc("create_success"));
      queryClient.invalidateQueries({ queryKey: ["company-members"] });
      setIsFormOpen(false);
    },
    onError: (error) => handleApiError(error),
  });

  const updateMutation = useMutation({
    mutationFn: ({ user_code, data }: { user_code: string; data: any }) =>
      updateCompanyMember(user_code, data),
    onSuccess: () => {
      message.success(tc("update_success"));
      queryClient.invalidateQueries({ queryKey: ["company-members"] });
      setIsFormOpen(false);
      setIsRoleModalOpen(false);
    },
    onError: (error) => handleApiError(error),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteCompanyMember,
    onSuccess: () => {
      message.success(tc("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["company-members"] });
    },
    onError: (error) => handleApiError(error),
  });

  const deActiveMutation = useMutation({
    mutationFn: deActiveUser,
    onSuccess: () => {
      message.success(t("toggle_active_success"));
      queryClient.invalidateQueries({ queryKey: ["company-members"] });
    },
    onError: (error) => handleApiError(error),
  });

  // Handlers
  const handleSearch = (values: any) => {
    setParams({ ...params, ...values, page: 1 });
  };

  const handleClear = () => {
    setParams({
      page: 1,
      limit: 10,
      search: "",
      register_type: "",
      company_id: "",
    });
  };

  const handleCreate = () => {
    setSelectedUser(null);
    setIsFormOpen(true);
  };

  const handleEdit = (record: IUserCompany) => {
    setSelectedUser(record);
    setIsFormOpen(true);
  };

  const handleDelete = (record: IUserCompany) => {
    deleteMutation.mutate(record.user_code);
  };

  const handleAssignRoles = (record: IUserCompany) => {
    setSelectedUser(record);
    setIsRoleModalOpen(true);
  };

  const handleToggleActive = (record: IUserCompany) => {
    deActiveMutation.mutate(record.user_code);
  };

  const handleFormFinish = async (values: any) => {
    if (selectedUser) {
      // Update
      const updateData: Partial<IUpdateUserCompanyData> = {
        full_name: values.full_name,
      };
      if (values.password) {
        updateData.password = values.password;
      }
      updateMutation.mutate({
        user_code: selectedUser.user_code,
        data: updateData,
      });
    } else {
      // Create
      createMutation.mutate(values as IUserCompanyData);
    }
  };

  const handleRoleFinish = async (
    addRoles: string[],
    removeRoles: string[],
  ) => {
    if (!selectedUser) return;
    const updateData = {
      full_name: selectedUser.full_name,
      register_type: [selectedUser.register_type],
      add_roles: addRoles,
      remove_roles: removeRoles,
    };
    updateMutation.mutate({
      user_code: selectedUser.user_code,
      data: updateData,
    });
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex justify="space-between">
        <UserFilter onSearch={handleSearch} onClear={handleClear} />

        {(companyMember.full || companyMember.create) && (
          <TooltipButton
            type="primary"
            icon={<PlusOutlined />}
            onClick={handleCreate}
            tooltip={t("create_title")}>
            {tc("add_new")}
          </TooltipButton>
        )}
      </Flex>

      <Card>
        <UserTable
          data={data?.data?.records || []}
          loading={isLoading}
          pagination={{
            current: params.page,
            pageSize: params.limit,
            total: data?.data?.total_records || 0,
            onChange: (page: number, limit: number) =>
              setParams({ ...params, page, limit }),
          }}
          onEdit={handleEdit}
          onDelete={handleDelete}
          onAssignRoles={handleAssignRoles}
          onToggleActive={handleToggleActive}
          permissions={companyMember}
        />
      </Card>

      <UserForm
        open={isFormOpen}
        onClose={() => setIsFormOpen(false)}
        onFinish={handleFormFinish}
        loading={
          createMutation.status === "pending" ||
          updateMutation.status === "pending"
        }
        record={selectedUser}
      />

      <RoleModal
        open={isRoleModalOpen}
        onClose={() => setIsRoleModalOpen(false)}
        onFinish={handleRoleFinish}
        loading={updateMutation.status === "pending"}
        record={selectedUser}
      />
    </Space>
  );
};

export default UserManager;
