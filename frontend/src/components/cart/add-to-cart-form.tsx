"use client"

import React, { useState } from "react";
import { useCart } from "@/providers/cart-context";
import { CourseConfig } from "@/lib/types";
import { Button } from "@/components/ui/button";

interface AddToCartFormProps {
  product?: CourseConfig | null;
}

export default function AddToCartForm({ product }: AddToCartFormProps) {
  const { addToCart, cartItems } = useCart();
  const [quantity, setQuantity] = useState(0);

  const handleAddToCart = () => {
    if (product) {
      addToCart(product);
      setQuantity(1);
    }
  };

  const isProductInCart =
    product && cartItems.some((item) => item.product.courseId === product.courseId);

  return (
    <div>
      <Button aria-label="Add to cart" onClick={handleAddToCart} disabled={isProductInCart ? true : undefined}>
        {isProductInCart ? "Added" : "Add to cart"}
      </Button>
    </div>
  );
}
