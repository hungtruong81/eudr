"use client";
import React, { useState } from "react";
import {
  Flex,
  Space,
  Tag,
  Row,
  Col,
  Card,
  Button,
  Typography,
  Empty,
  message,
  Modal,
} from "antd";
import {
  CheckOutlined,
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
  CarOutlined,
  DatabaseOutlined,
} from "@ant-design/icons";
import ThuMuaFilter from "./purchase-v2-filter";
import { formatVnCurrency } from "@/lib/utils";
import ThuMuaForm from "./purchase-v2-form";

const { Text } = Typography;

// Mock initial tickets for the demo UI
const MOCK_TICKETS = [
  {
    transaction_ticket_id: "TICKET-001",
    transaction_ticket_code: "TM-2026-0001",
    seller_name: "Nguyễn Văn A",
    buyer_name: "HTX Thu Mua Lộc Ninh",
    status: "completed",
    total_weight: 420,
    total_amount: 14700000,
    has_transport: true,
    transport_info: {
      driver_name: "Trần Văn Tài",
      driver_phone: "0901234567",
      license_plate: "93A-123.45",
      pickup_location: "Vườn cao su Nguyễn Văn A",
      delivery_location: "Nhà máy Chế biến Lộc Ninh",
    },
    bins: [
      {
        id: "1",
        name: "Bình 1",
        capacity: 100,
        poured: 80,
        quality: "loai_1",
        pH: 6.8,
      },
      {
        id: "2",
        name: "Bình 2",
        capacity: 100,
        poured: 90,
        quality: "loai_1",
        pH: 6.9,
      },
      {
        id: "3",
        name: "Bình 3",
        capacity: 120,
        poured: 120,
        quality: "loai_2",
        pH: 6.2,
      },
      {
        id: "4",
        name: "Bình 4",
        capacity: 120,
        poured: 130,
        quality: "loai_2",
        pH: 6.3,
      },
    ],
    created_at: "2026-06-12",
  },
  {
    transaction_ticket_id: "TICKET-002",
    transaction_ticket_code: "TM-2026-0002",
    seller_name: "Lê Thị B",
    buyer_name: "HTX Thu Mua Lộc Ninh",
    status: "pending",
    total_weight: 150,
    total_amount: 5250000,
    has_transport: false,
    tank_id: "tank-02",
    bins: [
      {
        id: "1",
        name: "Bình 1",
        capacity: 100,
        poured: 50,
        quality: "loai_1",
        pH: 7.0,
      },
      {
        id: "2",
        name: "Bình 2",
        capacity: 100,
        poured: 100,
        quality: "loai_1",
        pH: 7.1,
      },
    ],
    created_at: "2026-06-12",
  },
];

const ThuMua = () => {
  const [tickets, setTickets] = useState(MOCK_TICKETS);
  const [params, setParams] = useState({
    search: "",
    status: "all",
  });
  const [openForm, setOpenForm] = useState(false);
  const [selectedTicket, setSelectedTicket] = useState<any>(null);
  const [openDetail, setOpenDetail] = useState(false);
  const [viewTicket, setViewTicket] = useState<any>(null);

  const getStatusColor = (status: string) => {
    switch (status) {
      case "completed":
        return "success";
      case "pending":
        return "default";
      case "confirmed":
        return "warning";
      case "cancelled":
        return "error";
      default:
        return "default";
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case "completed":
        return "Đã hoàn thành";
      case "pending":
        return "Chờ xác nhận";
      case "confirmed":
        return "Đã xác nhận";
      case "cancelled":
        return "Đã hủy";
      default:
        return status?.toUpperCase();
    }
  };

  const handleSearch = (newParams: any) => {
    setParams((prev) => ({ ...prev, ...newParams }));
  };

  const handleConfirm = (code: string) => {
    setTickets((prev) =>
      prev.map((t) =>
        t.transaction_ticket_code === code ? { ...t, status: "completed" } : t,
      ),
    );
    message.success(`Đã xác nhận thành công phiếu ${code}`);
  };

  const handleCancel = (code: string) => {
    setTickets((prev) =>
      prev.map((t) =>
        t.transaction_ticket_code === code ? { ...t, status: "cancelled" } : t,
      ),
    );
    message.warning(`Đã hủy phiếu ${code}`);
  };

  const handleSave = (ticketData: any) => {
    if (selectedTicket) {
      // Editing
      setTickets((prev) =>
        prev.map((t) =>
          t.transaction_ticket_id === selectedTicket.transaction_ticket_id
            ? { ...t, ...ticketData }
            : t,
        ),
      );
      message.success("Cập nhật phiếu thu mua thành công!");
    } else {
      // Creating
      const newTicket = {
        transaction_ticket_id: `TICKET-${Date.now()}`,
        transaction_ticket_code: `TM-2026-${String(tickets.length + 1).padStart(4, "0")}`,
        status: "pending",
        created_at: new Date().toISOString().split("T")[0],
        ...ticketData,
      };
      setTickets((prev) => [newTicket, ...prev]);
      message.success("Tạo phiếu thu mua mới thành công!");
    }
    setOpenForm(false);
    setSelectedTicket(null);
  };

  const handleDelete = (id: string) => {
    setTickets((prev) => prev.filter((t) => t.transaction_ticket_id !== id));
    message.error("Đã xóa phiếu thu mua.");
  };

  const filteredTickets = tickets.filter((t) => {
    const matchesSearch =
      !params.search ||
      t.transaction_ticket_code
        .toLowerCase()
        .includes(params.search.toLowerCase()) ||
      t.seller_name.toLowerCase().includes(params.search.toLowerCase());
    const matchesStatus = params.status === "all" || t.status === params.status;
    return matchesSearch && matchesStatus;
  });

  if (openForm) {
    return (
      <ThuMuaForm
        initialValues={selectedTicket}
        onSave={handleSave}
        onBack={() => {
          setOpenForm(false);
          setSelectedTicket(null);
        }}
      />
    );
  }

  return (
    <Space direction="vertical" style={{ width: "100%" }} size="middle">
      <Flex align="center" justify="space-between" wrap gap="middle">
        <ThuMuaFilter onSearch={handleSearch} />

        <Button
          type="primary"
          icon={<PlusOutlined />}
          onClick={() => {
            setSelectedTicket(null);
            setOpenForm(true);
          }}
          style={{ height: 40, borderRadius: 6, fontWeight: 500 }}>
          Thêm phiếu thu mua
        </Button>
      </Flex>

      {filteredTickets.length === 0 ? (
        <Empty description="Không tìm thấy phiếu thu mua nào" />
      ) : (
        <Row gutter={[16, 16]} align="stretch">
          {filteredTickets.map((ticket) => {
            return (
              <Col
                xs={24}
                sm={24}
                md={12}
                lg={8}
                key={ticket.transaction_ticket_id}
                style={{ display: "flex" }}>
                <Card
                  hoverable
                  style={{
                    width: "100%",
                    display: "flex",
                    flexDirection: "column",
                    borderRadius: 8,
                    overflow: "hidden",
                    border: "1px solid #f0f0f0",
                  }}
                  styles={{
                    body: {
                      flex: 1,
                      padding: "20px",
                      display: "flex",
                      flexDirection: "column",
                      gap: "14px",
                    },
                  }}>
                  <Flex justify="space-between" align="center">
                    <Text strong style={{ fontSize: "16px", color: "#1f1f1f" }}>
                      {ticket.transaction_ticket_code}
                    </Text>
                    <Tag color={getStatusColor(ticket.status)}>
                      {getStatusLabel(ticket.status)}
                    </Tag>
                  </Flex>

                  <div
                    style={{
                      flex: 1,
                      display: "flex",
                      flexDirection: "column",
                      gap: "8px",
                    }}>
                    <div>
                      <Text type="secondary" style={{ fontSize: "12px" }}>
                        Người Bán
                      </Text>
                      <div
                        style={{
                          fontSize: "14px",
                          fontWeight: 500,
                          color: "#434343",
                        }}>
                        {ticket.seller_name}
                      </div>
                    </div>
                    <div>
                      <Text type="secondary" style={{ fontSize: "12px" }}>
                        Người Thu Mua / HTX
                      </Text>
                      <div style={{ fontSize: "14px", color: "#434343" }}>
                        {ticket.buyer_name}
                      </div>
                    </div>

                    <Flex
                      justify="space-between"
                      align="center"
                      style={{ marginTop: "6px" }}>
                      <div>
                        <Text type="secondary" style={{ fontSize: "12px" }}>
                          Khối lượng mủ
                        </Text>
                        <div style={{ fontSize: "14px", fontWeight: 600 }}>
                          {ticket.total_weight} kg
                        </div>
                      </div>
                      <div>
                        <Text type="secondary" style={{ fontSize: "12px" }}>
                          Thành tiền
                        </Text>
                        <div
                          style={{
                            fontSize: "14px",
                            fontWeight: 600,
                            color: "#1890ff",
                          }}>
                          {formatVnCurrency(ticket.total_amount)}
                        </div>
                      </div>
                    </Flex>

                    <Flex
                      align="center"
                      gap="small"
                      style={{ marginTop: "6px" }}>
                      {ticket.has_transport ? (
                        <Tag color="blue" icon={<CarOutlined />}>
                          Có Vận Chuyển
                        </Tag>
                      ) : (
                        <Tag color="orange" icon={<DatabaseOutlined />}>
                          Đổ Bồn Trực Tiếp ({ticket.tank_id || "Bồn chứa"})
                        </Tag>
                      )}
                      <Tag color="cyan">
                        {ticket.bins?.length || 0} bình con
                      </Tag>
                    </Flex>
                  </div>

                  <Flex
                    justify="space-between"
                    align="center"
                    style={{
                      borderTop: "1px solid #f5f5f5",
                      paddingTop: "12px",
                      marginTop: "auto",
                    }}>
                    <Text type="secondary" style={{ fontSize: "12px" }}>
                      {ticket.created_at}
                    </Text>

                    <Space size="small">
                      <Button
                        type="text"
                        icon={<EyeOutlined />}
                        onClick={() => {
                          setViewTicket(ticket);
                          setOpenDetail(true);
                        }}
                      />
                      {ticket.status === "pending" && (
                        <>
                          <Button
                            type="text"
                            icon={<EditOutlined />}
                            onClick={() => {
                              setSelectedTicket(ticket);
                              setOpenForm(true);
                            }}
                          />
                          <Button
                            type="text"
                            danger
                            icon={
                              <CheckOutlined style={{ color: "#52c41a" }} />
                            }
                            onClick={() =>
                              handleConfirm(ticket.transaction_ticket_code)
                            }
                          />
                          <Button
                            type="text"
                            danger
                            icon={<DeleteOutlined />}
                            onClick={() =>
                              handleDelete(ticket.transaction_ticket_id)
                            }
                          />
                        </>
                      )}
                    </Space>
                  </Flex>
                </Card>
              </Col>
            );
          })}
        </Row>
      )}

      {/* Ticket Detail Modal */}
      <Modal
        title={`Chi tiết phiếu thu mua: ${viewTicket?.transaction_ticket_code}`}
        open={openDetail}
        onCancel={() => setOpenDetail(false)}
        footer={[
          <Button
            key="close"
            type="primary"
            onClick={() => setOpenDetail(false)}>
            Đóng
          </Button>,
        ]}
        width={650}>
        {viewTicket && (
          <div
            style={{
              display: "flex",
              flexDirection: "column",
              gap: "16px",
              paddingTop: "12px",
            }}>
            <Row gutter={[16, 8]}>
              <Col span={12}>
                <strong>Người bán:</strong> {viewTicket.seller_name}
              </Col>
              <Col span={12}>
                <strong>Người thu mua:</strong> {viewTicket.buyer_name}
              </Col>
              <Col span={12}>
                <strong>Trạng thái:</strong>{" "}
                <Tag color={getStatusColor(viewTicket.status)}>
                  {getStatusLabel(viewTicket.status)}
                </Tag>
              </Col>
              <Col span={12}>
                <strong>Ngày tạo:</strong> {viewTicket.created_at}
              </Col>
              <Col span={12}>
                <strong>Tổng khối lượng:</strong> {viewTicket.total_weight} kg
              </Col>
              <Col span={12}>
                <strong>Tổng tiền:</strong>{" "}
                <span style={{ color: "#1890ff", fontWeight: "bold" }}>
                  {formatVnCurrency(viewTicket.total_amount)}
                </span>
              </Col>
            </Row>

            <div style={{ borderTop: "1px solid #f0f0f0", paddingTop: "12px" }}>
              <h4 style={{ marginBottom: "10px" }}>
                Danh sách bình con chứa mủ
              </h4>
              <Row gutter={[12, 12]}>
                {viewTicket.bins?.map((bin: any) => (
                  <Col span={12} key={bin.id}>
                    <Card size="small" styles={{ body: { padding: "10px" } }}>
                      <div
                        style={{
                          fontWeight: "bold",
                          fontSize: "14px",
                          color: "#1890ff",
                        }}>
                        {bin.name}
                      </div>
                      <div>
                        Khối lượng: {bin.poured} / {bin.capacity} kg
                      </div>
                      <div>
                        Chất lượng:{" "}
                        <Tag color="blue">
                          {bin.quality === "loai_1" ? "Loại 1" : "Loại 2"}
                        </Tag>
                      </div>
                      <div>
                        Độ pH:{" "}
                        <span style={{ fontWeight: "bold" }}>{bin.pH}</span>
                      </div>
                    </Card>
                  </Col>
                ))}
              </Row>
            </div>

            <div style={{ borderTop: "1px solid #f0f0f0", paddingTop: "12px" }}>
              <h4 style={{ marginBottom: "6px" }}>
                Thông tin Vận chuyển / Đổ bồn
              </h4>
              {viewTicket.has_transport ? (
                <div>
                  <div>
                    <strong>Tài xế:</strong>{" "}
                    {viewTicket.transport_info?.driver_name} -{" "}
                    {viewTicket.transport_info?.driver_phone}
                  </div>
                  <div>
                    <strong>Biển số xe:</strong>{" "}
                    {viewTicket.transport_info?.license_plate}
                  </div>
                  <div>
                    <strong>Điểm đi:</strong>{" "}
                    {viewTicket.transport_info?.pickup_location}
                  </div>
                  <div>
                    <strong>Điểm đến:</strong>{" "}
                    {viewTicket.transport_info?.delivery_location}
                  </div>
                </div>
              ) : (
                <div>
                  Đổ trực tiếp vào bồn chứa nhà máy:{" "}
                  <strong>
                    {viewTicket.tank_id === "tank-01"
                      ? "Bồn số 1 (Latex Tank 01)"
                      : "Bồn số 2 (Latex Tank 02)"}
                  </strong>
                </div>
              )}
            </div>
          </div>
        )}
      </Modal>
    </Space>
  );
};

export default ThuMua;
