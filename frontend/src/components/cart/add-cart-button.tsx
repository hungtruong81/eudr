"use client";

import React from "react";
import { useCart } from "@/providers/cart-context";
import { CourseConfig } from "@/lib/types";
import { Button } from "@/components/ui/button";
import { ShoppingCartIcon } from "lucide-react";

interface AddCartButtonProps {
  course: CourseConfig;
}

export function AddCartButton({ course }: AddCartButtonProps) {
  const { addToCart, cartItems, setIsCartShow } = useCart();

  interface HandleAddCartClickEvent
    extends React.MouseEvent<HTMLButtonElement, MouseEvent> {}

  const handleAddCartClick = (e: HandleAddCartClickEvent): void => {
    e.preventDefault();
    e.stopPropagation();
    addToCart(course);
    setIsCartShow(true);
  };
  const handleShowCartClick = (e: HandleAddCartClickEvent): void => {
    e.preventDefault();
    e.stopPropagation();
    setIsCartShow(true);
  };

  const existsInCart = cartItems.some(
    (item) => item.product.courseId === course.courseId
  );
  if (existsInCart) {
    return (
      <Button variant="outline" aria-label="Xem giỏ hàng" onClick={handleShowCartClick}>
        <ShoppingCartIcon className="mr-2" /> Xem giỏ hàng
      </Button>
    );
  }

  return (
    <Button aria-label="Thêm vào giỏ hàng" onClick={handleAddCartClick}>
      Thêm vào giỏ hàng
    </Button>
  );
}
