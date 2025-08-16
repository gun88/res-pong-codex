import { Directive, EventEmitter, HostListener, Input, Output } from '@angular/core';
import { Router } from '@angular/router';

@Directive({
  selector: '[rpSwipeNav]',
  standalone: true
})
export class SwipeNavDirective {
  @Input() nextLink?: string | any[];
  @Input() prevLink?: string | any[];

  @Input() thresholdPx = 60;      // distanza minima in px per considerare lo swipe
  @Input() maxDurationMs = 600;   // durata massima del gesto

  @Output() swipeLeft = new EventEmitter<void>();
  @Output() swipeRight = new EventEmitter<void>();

  private startX = 0;
  private startY = 0;
  private startTime = 0;
  private tracking = false;

  constructor(private router: Router) {}

  @HostListener('pointerdown', ['$event'])
  onPointerDown(ev: PointerEvent) {
    if (ev.pointerType !== 'touch' && ev.pointerType !== 'pen') return;
    this.tracking = true;
    this.startX = ev.clientX;
    this.startY = ev.clientY;
    this.startTime = performance.now();
  }

  @HostListener('pointerup', ['$event'])
  onPointerUp(ev: PointerEvent) {
    if (!this.tracking) return;
    this.tracking = false;

    const dx = ev.clientX - this.startX;
    const dy = ev.clientY - this.startY;
    const dt = performance.now() - this.startTime;

    if (dt > this.maxDurationMs) return;
    if (Math.abs(dx) < this.thresholdPx) return;
    if (Math.abs(dy) > Math.abs(dx)) return; // ignore diagonal/vertical drags

    if (dx < 0) {
      this.swipeLeft.emit();
      if (this.nextLink) this.router.navigate(Array.isArray(this.nextLink) ? this.nextLink : [this.nextLink]);
    } else {
      this.swipeRight.emit();
      if (this.prevLink) this.router.navigate(Array.isArray(this.prevLink) ? this.prevLink : [this.prevLink]);
    }
  }

  // Prevent default scrolling only for quick horizontal swipes in progress
  @HostListener('touchmove', ['$event'])
  onTouchMove(ev: TouchEvent) {
    if (!this.tracking) return;
    const touch = ev.touches[0];
    const dx = touch.clientX - this.startX;
    const dy = touch.clientY - this.startY;
    if (Math.abs(dx) > Math.abs(dy)) {
      ev.preventDefault();
    }
  }
}
