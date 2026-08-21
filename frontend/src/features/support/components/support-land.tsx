"use client";

import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import {
  deleteLand,
  getLands,
  IGetLandParams,
} from "@/features/manage-land/land/actions";
import { IPlot } from "@/features/manage-land/land/types";
import {
  CheckOutlined,
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, message, Space, Tag, Modal, Card, Input } from "antd";
import { useMemo, useState } from "react";
import LandSheet from "@/features/manage-land/land/components/land-sheet";
import ApproveLandModal from "@/features/manage-land/land/components/approve-land";
import { usePermissions } from "@/contexts/permission-context";
import { useRouter } from "next/navigation";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { handleApiError } from "@/lib/api-error";
import { getConnections } from "@/features/connect/manage-connect/actions";
import AppModal from "@/components/modal";

const SupportLand = () => {
  const router = useRouter();
  const { land } = usePermissions();
  const queryClient = useQueryClient();

  const [searchFarmer, setSearchFarmer] = useState("");

  const [selectedFarmerId, setSelectedFarmerId] = useState<number | null>(null);
  const [selectedFarmerName, setSelectedFarmerName] = useState<string>("");

  const [filter, setFilter] = useState<IGetLandParams>({
    page: 1,
    limit: 10,
  });

  const [isOpenLandListModal, setIsOpenLandListModal] = useState(false);
  const [isOpenLandSheet, setIsOpenLandSheet] = useState(false);
  const [selectedLand, setSelectedLand] = useState<IPlot | null>(null);

  const [isOpenApproveModal, setIsOpenApproveModal] = useState(false);
  const [selectedApproveLand, setSelectedApproveLand] = useState<IPlot | null>(
    null,
  );

  const { data: connectionsData, isLoading: isLoadingConnections } = useQuery({
    queryKey: ["support-farmer-connections"],
    queryFn: () =>
      getConnections({
        account_type: "farmer",
        status: "accepted",
        limit: 1000,
      }),
  });

  const farmers = useMemo(() => {
    if (!connectionsData?.data?.records) return [];
    const list = connectionsData.data.records.map((conn) => {
      const isSent = conn.connection_direction === "sent";
      const connectedUserId = isSent
        ? conn.target_user_id
        : conn.requester_user_id;

      return {
        key: connectedUserId.toString(),
        farmer_user_id: connectedUserId,
        farmer_name: conn.full_name,
        phone: conn.phone,
        email: conn.email,
        register_type: conn.register_type,
      };
    });

    if (searchFarmer) {
      return list.filter(
        (f) =>
          f.farmer_name.toLowerCase().includes(searchFarmer.toLowerCase()) ||
          f.phone.includes(searchFarmer) ||
          (f.email &&
            f.email.toLowerCase().includes(searchFarmer.toLowerCase())),
      );
    }
    return list;
  }, [connectionsData, searchFarmer]);

  const {
    data: landsData,
    isLoading: isLoadingLands,
    refetch: refetchLands,
  } = useQuery({
    queryKey: ["support-land", filter, selectedFarmerId],
    queryFn: () => getLands({ ...filter, farmer_user_id: selectedFarmerId! }),
    enabled: !!selectedFarmerId && isOpenLandListModal,
  });

  const deleteMutation = useMutation({
    mutationFn: (plot_code: string) => deleteLand(plot_code),
    onSuccess: () => {
      message.success("Xóa lô đất thành công!");
      queryClient.invalidateQueries({ queryKey: ["support-land"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const farmerColumns: CustomColumnTypeTable<any>[] = [
    {
      title: "Tên nông hộ",
      dataIndex: "farmer_name",
      render: (text) => <b>{text}</b>,
    },
    {
      title: "Số điện thoại",
      dataIndex: "phone",
    },
    {
      title: "Email",
      dataIndex: "email",
    },
    {
      title: "Hành động",
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          <TooltipButton
            tooltip="Xem danh sách lô đất"
            icon={<EyeOutlined />}
            type="dashed"
            onClick={() => {
              setSelectedFarmerId(record.farmer_user_id);
              setSelectedFarmerName(record.farmer_name);
              setIsOpenLandListModal(true);
            }}
          />

          <TooltipButton
            tooltip="Tạo lô đất"
            icon={<PlusOutlined />}
            type="primary"
            onClick={() => {
              setSelectedFarmerId(record.farmer_user_id);
              setSelectedFarmerName(record.farmer_name);
              setSelectedLand(null);
              setIsOpenLandSheet(true);
            }}
          />
        </Space>
      ),
    },
  ];

  const landColumns: CustomColumnTypeTable<IPlot>[] = [
    {
      title: "Mã lô đất",
      dataIndex: "plot_code",
      render: (text) => text.toUpperCase(),
    },
    {
      title: "Tỉnh thành",
      dataIndex: "province_name",
    },
    {
      title: "Diện tích (ha)",
      dataIndex: "land_area",
    },
    {
      title: "Trạng thái",
      dataIndex: "status",
    },
    {
      title: "Phê duyệt",
      dataIndex: "is_approved",
      render: (text) => (
        <Tag
          color={text === 0 ? "processing" : text === 1 ? "success" : "error"}>
          {text === 0
            ? "Chờ phê duyệt"
            : text === 1
              ? "Đã phê duyệt"
              : "Từ chối phê duyệt"}
        </Tag>
      ),
    },
    {
      title: "Ngày tạo",
      dataIndex: "created_at",
      type: "date",
    },
    {
      title: "Hành động",
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {land.full && (
            <TooltipButton
              tooltip="Duyệt lô đất"
              icon={<CheckOutlined />}
              type="default"
              onClick={() => {
                setIsOpenApproveModal(true);
                setSelectedApproveLand(record);
              }}
            />
          )}
          <TooltipButton
            tooltip="Chỉnh sửa"
            icon={<EditOutlined />}
            type="primary"
            ghost
            onClick={() => {
              setIsOpenLandSheet(true);
              setSelectedLand(record);
            }}
          />
          <TooltipButton
            tooltip="Xem chi tiết"
            icon={<EyeOutlined />}
            type="dashed"
            onClick={() => {
              router.push(`/support/land/${record.plot_code}`);
            }}
          />

          {(land.delete || land.full) && (
            <ConfirmTooltipButton
              confirmTitle="Xác nhận xóa"
              confirmDescription={`Bạn có chắc chắn muốn xóa lô đất "${record.plot_code}"?`}
              tooltip="Xóa"
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => deleteMutation.mutate(record.plot_code)}
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Card size="small" title="Danh sách kết nối với nông hộ">
        <Flex
          align="center"
          justify="space-between"
          style={{ marginBottom: 16 }}>
          <Input.Search
            placeholder="Tìm kiếm theo tên, SĐT, email..."
            allowClear
            onSearch={(val) => setSearchFarmer(val)}
            style={{ width: 300 }}
          />
        </Flex>

        <CustomTable
          rowKey="key"
          columns={farmerColumns}
          dataSource={farmers}
          loading={isLoadingConnections}
          tableId="support-farmer-list-table"
        />
      </Card>

      <AppModal
        title={`Danh sách lô đất của ${selectedFarmerName}`}
        open={isOpenLandListModal}
        onCancel={() => setIsOpenLandListModal(false)}
        footer={null}
        width={1200}>
        <Flex
          align="center"
          justify="space-between"
          style={{ marginBottom: 16 }}>
          <div></div>
          {(land.create || land.full) && (
            <TooltipButton
              tooltip="Thêm lô đất"
              icon={<PlusOutlined />}
              type="primary"
              onClick={() => {
                setSelectedLand(null);
                setIsOpenLandSheet(true);
              }}
            />
          )}
        </Flex>
        <CustomTable<IPlot>
          rowKey="plot_id"
          columns={landColumns}
          dataSource={landsData?.data?.records || []}
          loading={isLoadingLands}
          tableId="support-land-list-table"
          pagination={{
            current: landsData?.data?.current_page || 1,
            pageSize: landsData?.data?.page_limit || 10,
            total: landsData?.data?.total_records || 0,
          }}
          onChange={(pagination) => {
            setFilter({
              ...filter,
              page: pagination.current || 1,
              limit: pagination.pageSize || 10,
            });
          }}
          scroll={{
            x: "max-content",
          }}
        />
      </AppModal>

      <LandSheet
        onClose={() => {
          setIsOpenLandSheet(false);
          refetchLands(); // Refetch incase new land was added
        }}
        open={isOpenLandSheet}
        record={selectedLand}
        defaultFarmer={
          selectedFarmerId
            ? {
                farmer_user_id: selectedFarmerId,
                farmer_name: selectedFarmerName,
              }
            : undefined
        }
      />

      <ApproveLandModal
        open={isOpenApproveModal}
        onCancel={() => {
          setIsOpenApproveModal(false);
          refetchLands();
        }}
        record={selectedApproveLand}
      />
    </Space>
  );
};

export default SupportLand;
